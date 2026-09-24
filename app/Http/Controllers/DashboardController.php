<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AirtableException;
use App\Services\ActRequirements;
use App\Services\AirtableService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController
{
    private const SNAPSHOT_KEY = 'dashboard_snapshot';

    private const LAST_GOOD_KEY = 'dashboard_snapshot_last_good';

    public function __construct(
        private AirtableService $airtable,
        private ActRequirements $requirements,
    ) {}

    private array $docFields = [
        'ფოტო' => '📥 ფოტოები',
        'ხელმ.' => '📥 ხელმოწერები',
        'ნახაზი' => '📥 შიდა ნახაზები',
        'გამიჯ.' => '📥 გამიჯვნები',
        'ხარჯ.' => '📥 ხარჯთაღრიცხვა',
        'ტოპო' => '📥 ტოპო ნარგავები',
    ];

    private array $kvlevaColors = [
        '⏳ გამოსაკითხია' => '#c7381e',
        '✅ შესრულებულია' => '#0a7c42',
        '🔐 სახელმწიფო საკუთრება' => '#b3bac5',
        '🔢 არ გვაქვს ნომერი' => '#6b778c',
        '📍 ადგილზე შეავსებს' => '#d29922',
        '🚫 უარი თქვა' => '#1a1f36',
    ];

    /**
     * Acts + the moment they were fetched, cached as ONE entry so the
     * time shown always matches the data. On Airtable failure, fall back
     * to the last successful snapshot (marked stale).
     *
     * @return array{acts: array, fetched_at: int, stale: bool}
     *
     * @throws AirtableException when Airtable fails and no snapshot exists yet
     */
    private function loadSnapshot(int $ttlSeconds): array
    {
        try {
            $snapshot = Cache::remember(self::SNAPSHOT_KEY, $ttlSeconds, function () {
                $fresh = [
                    'acts' => $this->airtable->getAllActs(),
                    'fetched_at' => now()->timestamp,
                ];

                Cache::forever(self::LAST_GOOD_KEY, $fresh);

                return $fresh;
            });

            return $snapshot + ['stale' => false];
        } catch (AirtableException $e) {
            $lastGood = Cache::get(self::LAST_GOOD_KEY);

            if ($lastGood === null) {
                throw $e;
            }

            return $lastGood + ['stale' => true];
        }
    }

    public function index(Request $request): View
    {
        $cacheMinutes = (int) config('vigil.dashboard.cache_minutes', 5);
        $snapshot = $this->loadSnapshot($cacheMinutes * 60);

        $data = $snapshot['acts'];
        $fetchedAt = Carbon::createFromTimestamp($snapshot['fetched_at'], config('app.timezone'));

        $activeStatus = $request->query('status');
        $activeMissing = $request->query('missing');
        $activeKvleva = $request->query('kvleva');
        $activeBiznes = $request->query('biznes');

        $acts = collect($data)
            ->map(function ($act) {
                $fields = $act['fields'];

                return array_merge($fields, [
                    '_id' => $act['id'],
                    '_required' => $this->requirements->requiredDocFields($fields),
                    '_biznes_count' => count($fields['🏢 ბიზნესი'] ?? []),
                    '_has_corr' => ! empty($fields['📨 კორესპონდენცია'] ?? []),
                    '_kvleva' => $fields['📊 კვლევა'] ?? null,
                ]);
            })
            ->when($activeStatus === 'complete', fn ($col) => $col->filter(
                fn ($f) => ($f['🚦სტატუსი'] ?? '') === '✅ დასრულებული'
            ))
            ->when($activeStatus === 'incomplete', fn ($col) => $col->filter(
                fn ($f) => ($f['🚦სტატუსი'] ?? '') === '⏳ მიმდინარე'
            ))
            ->when($activeMissing, function ($col) use ($activeMissing) {
                $fieldMap = [
                    'photo' => '📥 ფოტოები',
                    'signatures' => '📥 ხელმოწერები',
                    'drawings' => '📥 შიდა ნახაზები',
                    'boundary' => '📥 გამიჯვნები',
                    'cost' => '📥 ხარჯთაღრიცხვა',
                    'topo' => '📥 ტოპო ნარგავები',
                ];
                $field = $fieldMap[$activeMissing] ?? null;

                return $col->filter(function ($f) use ($field) {
                    if (! $field) {
                        return true;
                    }

                    return in_array($field, $f['_required'])
                        && ! ($f[$field] ?? false);
                });
            })
            ->when($activeKvleva, fn ($col) => $col->filter(
                fn ($f) => ($f['_kvleva'] ?? '') === $activeKvleva
            ))
            ->when($activeBiznes === 'yes', fn ($col) => $col->filter(
                fn ($f) => $f['_biznes_count'] > 0
            ))
            ->sortBy(function ($f) {
                $num = $f['🔖 აქტის ნომერი'] ?? '0';
                $parts = explode('-', $num);

                return [(int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0)];
            })
            ->values();

        $total = count($data);
        $complete = collect($data)->filter(
            fn ($a) => ($a['fields']['🚦სტატუსი'] ?? '') === '✅ დასრულებული'
        )->count();

        // Footer counts from filtered acts
        $filteredTotal = $acts->count();
        $filteredComplete = $acts->filter(
            fn ($f) => ($f['🚦სტატუსი'] ?? '') === '✅ დასრულებული'
        )->count();
        $filteredIncomplete = $filteredTotal - $filteredComplete;
        $filteredBiznes = $acts->filter(fn ($f) => $f['_biznes_count'] > 0)->count();
        $filteredKvlevaPending = $acts->filter(
            fn ($f) => ($f['_kvleva'] ?? '') === '⏳ გამოსაკითხია'
        )->count();

        $hasActiveFilter = $activeStatus || $activeMissing || $activeKvleva || $activeBiznes;

        return view('dashboard', [
            'acts' => $acts,
            'docFields' => $this->docFields,
            'kvlevaColors' => $this->kvlevaColors,
            'total' => $total,
            'complete' => $complete,
            'incomplete' => $total - $complete,
            'fetchedAt' => $fetchedAt,
            'isStale' => $snapshot['stale'],
            'cacheMinutes' => $cacheMinutes,
            'statsUrl' => config('vigil.dashboard.airtable_stats_url'),
            'activeStatus' => $activeStatus,
            'activeMissing' => $activeMissing,
            'activeKvleva' => $activeKvleva,
            'activeBiznes' => $activeBiznes,
            'hasActiveFilter' => $hasActiveFilter,
            'filteredTotal' => $filteredTotal,
            'filteredComplete' => $filteredComplete,
            'filteredIncomplete' => $filteredIncomplete,
            'filteredBiznes' => $filteredBiznes,
            'filteredKvlevaPending' => $filteredKvlevaPending,
        ]);
    }
}
