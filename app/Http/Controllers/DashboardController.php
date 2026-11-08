<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AirtableException;
use App\Jobs\DriveDownloadJob;
use App\Jobs\DrivePhotosDownloadJob;
use App\Jobs\DriveRescanJob;
use App\Models\ActExcelEntry;
use App\Services\ActRequirements;
use App\Services\AirtableService;
use App\Support\ActRangeHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController
{
    public const SNAPSHOT_KEY = 'dashboard_snapshot';

    private const LAST_GOOD_KEY = 'dashboard_snapshot_last_good';

    public function __construct(
        private AirtableService $airtable,
        private ActRequirements $requirements,
    ) {}

    private array $docFields = [
        'ფოტო' => ['airtable' => '📥 ფოტოები',       'drive' => 'ფოტოები'],
        'ხელმ.' => ['airtable' => '📥 ხელმოწერები',    'drive' => null],
        'ნახაზი' => ['airtable' => '📥 შიდა ნახაზები',  'drive' => 'შიდა ნახაზები'],
        'გამიჯ.' => ['airtable' => '📥 გამიჯვნები',     'drive' => 'გამიჯვნები'],
        'ხარჯ.' => ['airtable' => '📥 ხარჯთაღრიცხვა', 'drive' => 'ხარჯთაღრიცხვა'],
        'ტოპო' => ['airtable' => '📥 ტოპო ნარგავები', 'drive' => 'ტოპო ნარგავები'],
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
    private function loadDriveStates(): array
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
                $name = $info['name'] ?? null;
                $actFolderId = $info['act_folder_id'] ?? null;
                $recordId = $actFolderId ? ($folderToRecord[$actFolderId] ?? null) : null;

                if (! $name || ! $recordId) {
                    continue;
                }

                $subState = $subfolderStates[$subfolderId] ?? 'empty';

                if (! isset($map[$recordId][$name]) || $map[$recordId][$name] !== 'full') {
                    $map[$recordId][$name] = $subState;
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
        $driveStates = $this->loadDriveStates();

        $data = $snapshot['acts'];
        $fetchedAt = Carbon::createFromTimestamp($snapshot['fetched_at'], config('app.timezone'));

        $activeStatus = $request->query('status');
        $activeMissing = $request->query('missing');
        $activeKvleva = $request->query('kvleva');
        $activeBiznes = $request->query('biznes');
        $activeHas = $request->query('has');
        $activeExcel = $request->query('excel');

        try {
            $enteredIds = ActExcelEntry::pluck('act_record_id')->flip()->all();
        } catch (\Throwable) {
            $enteredIds = [];
        }

        $acts = collect($data)
            ->map(function ($act) use ($driveStates, $enteredIds) {
                $fields = $act['fields'];
                $actDrive = $driveStates[$act['id']] ?? [];

                $driveDoc = [];
                foreach ($this->docFields as $label => $cfg) {
                    $subfolder = $cfg['drive'];
                    if ($subfolder === null) {
                        $driveDoc[$cfg['airtable']] = (bool) ($fields[$cfg['airtable']] ?? false);
                    } else {
                        $driveDoc[$cfg['airtable']] = isset($actDrive[$subfolder])
                            ? $actDrive[$subfolder] === 'full'
                            : (bool) ($fields[$cfg['airtable']] ?? false);
                    }
                }

                return array_merge($fields, [
                    '_id' => $act['id'],
                    '_url' => $this->airtable->recordUrl(config('vigil.airtable.tables.acts'), $act['id']),
                    '_required' => $this->requirements->requiredDocFields($fields),
                    '_biznes_count' => count($fields['🏢 ბიზნესი'] ?? []),
                    '_has_corr' => ! empty($fields['📨 კორესპონდენცია'] ?? []),
                    '_kvleva' => $fields['📊 კვლევა'] ?? null,
                    '_scan' => $actDrive['სკანი'] ?? null,
                    '_excel_entered' => isset($enteredIds[$act['id']]),
                    '_drive_doc' => $driveDoc,
                ]);
            })
            ->when($activeStatus === 'incomplete', fn ($col) => $col->filter(
                fn ($f) => ($f['🚦სტატუსი'] ?? '') === '⏳ მიმდინარე'
            ))
            ->when($activeStatus === 'incomplete_docs', fn ($col) => $col->filter(
                fn ($f) => ($f['🚦სტატუსი'] ?? '') === '✅ დასრულებული'
                    && (
                        ($f['_scan'] ?? null) !== 'full' ||
                        collect($f['_required'])->contains(fn ($field) => ! ($f['_drive_doc'][$field] ?? false))
                    )
            ))
            ->when($activeStatus === 'closed', fn ($col) => $col->filter(
                fn ($f) => ($f['🚦სტატუსი'] ?? '') === '✅ დასრულებული'
                    && collect($f['_required'])->every(fn ($field) => $f['_drive_doc'][$field] ?? false)
                    && ($f['_scan'] ?? null) === 'full'
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
                        && ! ($f['_drive_doc'][$field] ?? false);
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
                        && ($f['_drive_doc'][$field] ?? false);
                });
            })
            ->when($activeKvleva, fn ($col) => $col->filter(
                fn ($f) => ($f['_kvleva'] ?? '') === $activeKvleva
            ))
            ->when($activeBiznes === 'yes', fn ($col) => $col->filter(
                fn ($f) => $f['_biznes_count'] > 0
            ))
            ->when($activeExcel === 'entered', fn ($col) => $col->filter(
                fn ($f) => $f['_excel_entered'] === true
            ))
            ->when($activeExcel === 'not_entered', fn ($col) => $col->filter(
                fn ($f) => ($f['_drive_doc']['📥 ხარჯთაღრიცხვა'] ?? false) === true
                    && $f['_excel_entered'] === false
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

        // Acts where status=done AND scan=full AND all required docs present
        $closed = collect($data)->filter(function ($a) use ($driveStates) {
            $fields = $a['fields'];
            if (($fields['🚦სტატუსი'] ?? '') !== '✅ დასრულებული') {
                return false;
            }
            $actDrive = $driveStates[$a['id']] ?? [];
            if (($actDrive['სკანი'] ?? null) !== 'full') {
                return false;
            }
            $required = $this->requirements->requiredDocFields($fields);
            foreach ($required as $field) {
                $cfg = collect($this->docFields)->firstWhere('airtable', $field);
                $subfolder = $cfg['drive'] ?? null;
                $present = $subfolder
                    ? (($actDrive[$subfolder] ?? null) === 'full')
                    : (bool) ($fields[$field] ?? false);
                if (! $present) {
                    return false;
                }
            }

            return true;
        })->count();

        $filteredTotal = $acts->count();
        $filteredComplete = $acts->filter(
            fn ($f) => ($f['🚦სტატუსი'] ?? '') === '✅ დასრულებული'
        )->count();
        $filteredIncomplete = $filteredTotal - $filteredComplete;
        $filteredBiznes = $acts->filter(fn ($f) => $f['_biznes_count'] > 0)->count();
        $filteredKvlevaPending = $acts->filter(
            fn ($f) => ($f['_kvleva'] ?? '') === '⏳ გამოსაკითხია'
        )->count();

        $hasActiveFilter = $activeStatus || $activeMissing || $activeHas || $activeKvleva || $activeBiznes || $activeExcel;

        $rescanRunning = (bool) Cache::get('rescan_running', false);

        // Legacy full-range scan download (backwards compat)
        $downloadRunning = (bool) Cache::get('download_running', false);
        $downloadReady = Cache::has('download_ready_path');
        $downloadProgress = Cache::get('download_progress');

        // Slug-aware scans download state (Tools modal)
        $scanSlug = $request->query('scan_range', Cache::get('download_scan_last_slug', 'all'));
        $downloadScanRunning = (bool) Cache::get("download_scan_running:{$scanSlug}", false);
        $downloadScanReady = Cache::has("download_scan_ready_path:{$scanSlug}");
        $downloadScanProgress = Cache::get("download_scan_progress:{$scanSlug}");

        // Slug-aware photos download state (Tools modal)
        $photosSlug = $request->query('photos_range', Cache::get('download_photos_last_slug'));
        $downloadPhotosRunning = $photosSlug ? (bool) Cache::get("download_photos_running:{$photosSlug}", false) : false;
        $downloadPhotosReady = $photosSlug ? Cache::has("download_photos_ready_path:{$photosSlug}") : false;
        $downloadPhotosProgress = $photosSlug ? Cache::get("download_photos_progress:{$photosSlug}") : null;

        return view('dashboard', [
            'acts' => $acts,
            'docFields' => $this->docFields,
            'kvlevaColors' => $this->kvlevaColors,
            'total' => $total,
            'complete' => $complete,
            'incomplete' => $total - $complete,
            'closed' => $closed,
            'incompleteDocs' => $complete - $closed,
            'fetchedAt' => $fetchedAt,
            'isStale' => $snapshot['stale'],
            'cacheMinutes' => $cacheMinutes,
            'statsUrl' => config('vigil.dashboard.airtable_stats_url'),
            'activeStatus' => $activeStatus,
            'activeMissing' => $activeMissing,
            'activeKvleva' => $activeKvleva,
            'activeBiznes' => $activeBiznes,
            'activeHas' => $activeHas,
            'activeExcel' => $activeExcel,
            'hasActiveFilter' => $hasActiveFilter,
            'rescanRunning' => $rescanRunning,
            'downloadRunning' => $downloadRunning,
            'downloadReady' => $downloadReady,
            'downloadProgress' => $downloadProgress,
            'downloadScanRunning' => $downloadScanRunning,
            'downloadScanReady' => $downloadScanReady,
            'downloadScanProgress' => $downloadScanProgress,
            'scanSlug' => $scanSlug,
            'downloadPhotosRunning' => $downloadPhotosRunning,
            'downloadPhotosReady' => $downloadPhotosReady,
            'downloadPhotosProgress' => $downloadPhotosProgress,
            'photosSlug' => $photosSlug ?? 'all',
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

        Cache::put('rescan_running', true, now()->addHours(2));
        DriveRescanJob::dispatch();

        return redirect()->route('dashboard')->with('success', 'სრული სკანი დაიწყო.');
    }

    /**
     * Dispatch a slug-aware სკანი download job (manager only).
     * POST body: range={slug}  (optional — defaults to 'all')
     */
    public function download(Request $request): RedirectResponse
    {
        if ($request->attributes->get('vigil_role') !== 'manager') {
            abort(403);
        }

        $slug = $request->input('range', 'all');
        $actRange = ActRangeHelper::rangeFromSlug($slug);

        if (Cache::get("download_scan_running:{$slug}")) {
            return redirect()->route('dashboard')->with('error', 'სკანების გადმოწერა უკვე მიმდინარეობს');
        }

        Cache::put("download_scan_running:{$slug}", true, now()->addMinutes(60));
        Cache::put('download_scan_last_slug', $slug, now()->addHours(2));
        Cache::forget("download_scan_ready_path:{$slug}");
        Cache::forget("download_scan_ready_name:{$slug}");
        DriveDownloadJob::dispatch($actRange);

        return redirect()->route('dashboard')->with('success', '⏳ სკანების გადმოწერა დაიწყო');
    }

    public function downloadFile(Request $request): StreamedResponse|RedirectResponse
    {
        $slug = $request->query('range', 'all');
        $path = Cache::get("download_scan_ready_path:{$slug}");
        $name = Cache::get("download_scan_ready_name:{$slug}", "scans-{$slug}.zip");

        if (! $path || ! file_exists($path)) {
            return redirect()->route('dashboard')->with('error', 'ფაილი არ არის მზად ან ვადა გავიდა');
        }

        $size = filesize($path);

        return response()->stream(function () use ($path, $slug): void {
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                return;
            }

            while (! feof($handle)) {
                echo fread($handle, 1024 * 1024);
                flush();
            }

            fclose($handle);

            @unlink($path);
            Cache::forget("download_scan_ready_path:{$slug}");
            Cache::forget("download_scan_ready_name:{$slug}");
            Cache::forget("download_scan_running:{$slug}");
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'Content-Length' => (string) $size,
        ]);
    }

    /**
     * Dispatch a slug-aware ფოტოები download job (manager only).
     * POST body: range={slug}  (required)
     */
    public function downloadPhotos(Request $request): RedirectResponse
    {
        if ($request->attributes->get('vigil_role') !== 'manager') {
            abort(403);
        }

        $slug = $request->input('range');

        if (! $slug) {
            return redirect()->route('dashboard')->with('error', 'ფოტოების გადმოწერისთვის საჭიროა range');
        }

        $actRange = ActRangeHelper::rangeFromSlug($slug);

        if ($actRange && ($actRange['to'] - $actRange['from']) > 99) {
            return redirect()->route('dashboard')->with('error', 'ფოტოების range მაქსიმუმ 100 ACT');
        }

        if (Cache::get("download_photos_running:{$slug}")) {
            return redirect()->route('dashboard')->with('error', 'ფოტოების გადმოწერა უკვე მიმდინარეობს');
        }

        Cache::put("download_photos_running:{$slug}", true, now()->addMinutes(60));
        Cache::put('download_photos_last_slug', $slug, now()->addHours(2));
        Cache::forget("download_photos_ready_path:{$slug}");
        Cache::forget("download_photos_ready_name:{$slug}");
        DrivePhotosDownloadJob::dispatch($actRange);

        return redirect()->route('dashboard')->with('success', '⏳ ფოტოების გადმოწერა დაიწყო');
    }

    /**
     * Stream the ready photos ZIP for a given range slug.
     * GET /dashboard/download/photos/file?range={slug}
     */
    public function downloadPhotosFile(Request $request): StreamedResponse|RedirectResponse
    {
        $slug = $request->query('range');

        if (! $slug) {
            return redirect()->route('dashboard')->with('error', 'range პარამეტრი სავალდებულოა');
        }

        $path = Cache::get("download_photos_ready_path:{$slug}");
        $name = Cache::get("download_photos_ready_name:{$slug}", "photos-{$slug}.zip");

        if (! $path || ! file_exists($path)) {
            return redirect()->route('dashboard')->with('error', 'ფაილი არ არის მზად ან ვადა გავიდა');
        }

        $size = filesize($path);

        return response()->stream(function () use ($path, $slug): void {
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                return;
            }

            while (! feof($handle)) {
                echo fread($handle, 1024 * 1024);
                flush();
            }

            fclose($handle);

            @unlink($path);
            Cache::forget("download_photos_ready_path:{$slug}");
            Cache::forget("download_photos_ready_name:{$slug}");
            Cache::forget("download_photos_running:{$slug}");
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'Content-Length' => (string) $size,
        ]);
    }

    /**
     * JSON status endpoint

    /**
     * JSON status endpoint for download progress polling.
     * GET /dashboard/download/status?type=scans|photos&range={slug}
     */
    public function downloadStatus(Request $request): JsonResponse
    {
        $type = $request->query('type', 'scans');
        $slug = $request->query('range', 'all');

        if ($type === 'photos') {
            return response()->json([
                'running' => (bool) Cache::get("download_photos_running:{$slug}", false),
                'ready' => Cache::has("download_photos_ready_path:{$slug}"),
                'progress' => Cache::get("download_photos_progress:{$slug}"),
            ]);
        }

        return response()->json([
            'running' => (bool) Cache::get("download_scan_running:{$slug}", false),
            'ready' => Cache::has("download_scan_ready_path:{$slug}"),
            'progress' => Cache::get("download_scan_progress:{$slug}"),
        ]);
    }

    /**
     * Cancel a pending download — delete the ZIP and clear cache keys.
     * DELETE /dashboard/download/cancel?type=scans|photos&range={slug}
     */
    public function downloadCancel(Request $request): JsonResponse
    {
        $type = $request->query('type', 'scans');
        $slug = $request->query('range', 'all');

        if ($type === 'photos') {
            $path = Cache::get("download_photos_ready_path:{$slug}");
            if ($path && file_exists($path)) {
                @unlink($path);
            }
            Cache::forget("download_photos_ready_path:{$slug}");
            Cache::forget("download_photos_ready_name:{$slug}");
            Cache::forget("download_photos_running:{$slug}");
        } else {
            $path = Cache::get("download_scan_ready_path:{$slug}");
            if ($path && file_exists($path)) {
                @unlink($path);
            }
            Cache::forget("download_scan_ready_path:{$slug}");
            Cache::forget("download_scan_ready_name:{$slug}");
            Cache::forget("download_scan_running:{$slug}");
        }

        return response()->json(['ok' => true]);
    }
}
