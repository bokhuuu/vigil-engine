<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AirtableException;
use App\Jobs\DriveRescanJob;
use App\Services\ActRequirements;
use App\Services\AirtableService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController
{
    public const SNAPSHOT_KEY = 'dashboard_snapshot';

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

    /**
     * Build a map of ACT record ID → სკანი subfolder state.
     * Reads drive-watcher-state.json directly; returns [] on any error.
     */
    private function loadScanStates(): array
    {
        try {
            $raw = file_get_contents(storage_path('app/google/drive-watcher-state.json'));
            if (! $raw) {
                return [];
            }
            $state = json_decode($raw, true);
            $folderIndex = $state['folder_index'] ?? [];
            $subfolderIndex = $state['subfolder_index'] ?? [];
            $subfolderStates = $state['subfolder_states'] ?? [];

            $folderToRecord = [];
            foreach ($folderIndex as $driveFolderId => $info) {
                $recordId = $info['act_record_id'] ?? null;
                if ($recordId) {
                    $folderToRecord[$driveFolderId] = $recordId;
                }
            }

            $map = [];
            foreach ($subfolderIndex as $subfolderId => $info) {
                if (($info['name'] ?? '') === 'სკანი') {
                    $actFolderId = $info['act_folder_id'] ?? null;
                    $recordId = $actFolderId ? ($folderToRecord[$actFolderId] ?? null) : null;
                    if ($recordId) {
                        $existing = $map[$recordId] ?? null;
                        $new = $subfolderStates[$subfolderId] ?? null;
                        if ($existing !== 'full') {
                            $map[$recordId] = $new;
                        }
                    }
                }
            }

            return $map;
        } catch (\Throwable) {
            return [];
        }
    }

    public function index(Request $request): View
    {
        $cacheMinutes = (int) config('vigil.dashboard.cache_minutes', 5);
        $snapshot = $this->loadSnapshot($cacheMinutes * 60);
        $scanStates = $this->loadScanStates();

        $data = $snapshot['acts'];
        $fetchedAt = Carbon::createFromTimestamp($snapshot['fetched_at'], config('app.timezone'));

        $activeStatus = $request->query('status');
        $activeMissing = $request->query('missing');
        $activeKvleva = $request->query('kvleva');
        $activeBiznes = $request->query('biznes');
        $activeHas = $request->query('has');

        $acts = collect($data)
            ->map(function ($act) {
                $fields = $act['fields'];

                return array_merge($fields, [
                    '_id' => $act['id'],
                    '_url' => $this->airtable->recordUrl(config('vigil.airtable.tables.acts'), $act['id']),
                    '_required' => $this->requirements->requiredDocFields($fields),
                    '_biznes_count' => count($fields['🏢 ბიზნესი'] ?? []),
                    '_has_corr' => ! empty($fields['📨 კორესპონდენცია'] ?? []),
                    '_kvleva' => $fields['📊 კვლევა'] ?? null,
                    '_scan' => $scanStates[$act['id']] ?? null,
                ]);
            })
            ->when($activeStatus === 'complete', fn ($col) => $col->filter(
                fn ($f) => ($f['🚦სტატუსი'] ?? '') === '✅ დასრულებული'
            ))
            ->when($activeStatus === 'incomplete', fn ($col) => $col->filter(
                fn ($f) => ($f['🚦სტატუსი'] ?? '') === '⏳ მიმდინარე'
            ))
            ->when($activeStatus === 'closed', fn ($col) => $col->filter(
                fn ($f) => ($f['🚦სტატუსი'] ?? '') === '✅ დასრულებული'
                    && collect($f['_required'])->every(fn ($field) => $f[$field] ?? false)
            ))
            ->when($activeMissing, function ($col) use ($activeMissing) {
                $fieldMap = [
                    'scan' => '_scan',
                    'photo' => '📥 ფოტოები',
                    'signatures' => '📥 ხელმოწერები',
                    'drawings' => '📥 შიდა ნახაზები',
                    'boundary' => '📥 გამიჯვნები',
                    'cost' => '📥 ხარჯთაღრიცხვა',
                    'topo' => '📥 ტოპო ნარგავები',
                ];
                $field = $fieldMap[$activeMissing] ?? null;

                return $col->filter(function ($f) use ($field, $activeMissing) {
                    if (! $field) {
                        return true;
                    }
                    if ($activeMissing === 'scan') {
                        return ($f['_scan'] ?? null) !== 'full';
                    }

                    return in_array($field, $f['_required'])
                        && ! ($f[$field] ?? false);
                });
            })->when($activeHas, function ($col) use ($activeHas) {
                $fieldMap = [
                    'scan' => '_scan',
                    'photo' => '📥 ფოტოები',
                    'signatures' => '📥 ხელმოწერები',
                    'drawings' => '📥 შიდა ნახაზები',
                    'boundary' => '📥 გამიჯვნები',
                    'cost' => '📥 ხარჯთაღრიცხვა',
                    'topo' => '📥 ტოპო ნარგავები',
                ];
                $field = $fieldMap[$activeHas] ?? null;

                return $col->filter(function ($f) use ($field, $activeHas) {
                    if (! $field) {
                        return true;
                    }
                    if ($activeHas === 'scan') {
                        return ($f['_scan'] ?? null) === 'full';
                    }

                    return in_array($field, $f['_required'])
                        && ($f[$field] ?? false);
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

        $filteredTotal = $acts->count();
        $filteredComplete = $acts->filter(
            fn ($f) => ($f['🚦სტატუსი'] ?? '') === '✅ დასრულებული'
        )->count();
        $filteredIncomplete = $filteredTotal - $filteredComplete;
        $filteredBiznes = $acts->filter(fn ($f) => $f['_biznes_count'] > 0)->count();
        $filteredKvlevaPending = $acts->filter(
            fn ($f) => ($f['_kvleva'] ?? '') === '⏳ გამოსაკითხია'
        )->count();

        $hasActiveFilter = $activeStatus || $activeMissing || $activeHas || $activeKvleva || $activeBiznes;

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
            'scanStates' => $scanStates,
            'activeHas' => $activeHas,
            'hasActiveFilter' => $hasActiveFilter,
            'role' => $request->attributes->get('vigil_role', 'manager'),
            'filteredTotal' => $filteredTotal,
            'filteredComplete' => $filteredComplete,
            'filteredIncomplete' => $filteredIncomplete,
            'filteredBiznes' => $filteredBiznes,
            'filteredKvlevaPending' => $filteredKvlevaPending,
        ]);
    }

    /**
     * Dispatch a full Drive rescan job (manager only).
     */
    public function rescan(Request $request): RedirectResponse
    {
        if ($request->attributes->get('vigil_role') !== 'manager') {
            abort(403);
        }

        DriveRescanJob::dispatch();

        return redirect()->route('dashboard')->with('success', 'სრული სკანი დაიწყო.');
    }
}
