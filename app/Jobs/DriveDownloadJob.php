<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\DriveWatcherStateRepository;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use App\Support\ActRangeHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class DriveDownloadJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 3;

    public function __construct(
        public readonly ?array $actRange = null,
    ) {}

    public function handle(
        DriveWatcherStateRepository $stateRepo,
        GoogleDriveService $drive,
        TelegramService $telegram,
    ): void {
        $slug = ActRangeHelper::slugForRange($this->actRange);
        $label = ActRangeHelper::labelForRange($this->actRange);

        Log::info("DriveDownloadJob: starting სკანი download [{$label}]");

        $allFolderIndex = $stateRepo->getFolderIndex();

        if (empty($allFolderIndex)) {
            Log::warning('DriveDownloadJob: folder_index is empty — aborting');
            Cache::forget("download_scan_running:{$slug}");

            return;
        }

        $folderIndex = ActRangeHelper::filterFolderIndex($allFolderIndex, $this->actRange);
        $total = count($folderIndex);

        if ($total === 0) {
            Log::warning("DriveDownloadJob: no ACTs found in range [{$label}] — aborting");
            Cache::forget("download_scan_running:{$slug}");

            return;
        }

        // ── Checkpoint / resume logic ────────────────────────────────────
        // On retry: a run_id already exists → resume from last checkpoint
        // On first run: generate a new run_id and clear any stale state

        $existingRunId = Cache::get("download_scan_run_id:{$slug}");
        $checkpoint = Cache::get("download_scan_checkpoint:{$slug}");
        $isResume = $existingRunId !== null && $checkpoint !== null;

        if ($isResume) {
            $runId = $existingRunId;
            Log::info("DriveDownloadJob: resuming run {$runId} from checkpoint {$checkpoint}");
        } else {
            $runId = Str::uuid()->toString();
            Cache::put("download_scan_run_id:{$slug}", $runId, now()->addHours(4));
            Cache::forget("download_scan_checkpoint:{$slug}");
            Cache::forget("download_scan_skipped_warnings:{$slug}");
            Cache::forget('download_skipped_files');
            Log::info("DriveDownloadJob: new run {$runId}");
        }

        // ── ZIP setup ────────────────────────────────────────────────────
        $timestamp = now()->format('Y-m-d_His');
        $downloadsDir = storage_path('app/downloads');

        if (! is_dir($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        // On resume: reuse the zip path stored in cache; on first run: create a new path
        $zipPath = Cache::get("download_scan_zip_path:{$slug}:{$runId}")
            ?? "{$downloadsDir}/scans-{$slug}-{$timestamp}.zip";

        Cache::put("download_scan_zip_path:{$slug}:{$runId}", $zipPath, now()->addHours(4));

        $zip = new ZipArchive;

        // On resume: open existing ZIP for appending (no OVERWRITE — that would destroy it)
        // On first run: create fresh ZIP
        $zipFlags = ($isResume && file_exists($zipPath))
            ? ZipArchive::CREATE
            : ZipArchive::CREATE | ZipArchive::OVERWRITE;

        if ($zip->open($zipPath, $zipFlags) !== true) {
            Log::error("DriveDownloadJob: cannot open ZIP at {$zipPath}");
            Cache::forget("download_scan_running:{$slug}");

            return;
        }

        // ── Process ACTs ─────────────────────────────────────────────────
        $downloaded = 0;
        $skipped = 0;
        $failed = 0;
        $processed = 0;
        $pastCheckpoint = ! $isResume;

        foreach ($folderIndex as $actFolderId => $actInfo) {
            $actNumber = (string) ($actInfo['act_number'] ?? '');

            // Skip entries already processed before the checkpoint
            if (! $pastCheckpoint) {
                if ($actFolderId === $checkpoint) {
                    $pastCheckpoint = true;
                }
                $processed++;

                continue;
            }

            $processed++;

            // Update progress every 5 ACTs or on the first
            if ($processed % 5 === 0 || $processed === 1) {
                Cache::put("download_scan_progress:{$slug}", [
                    'done' => $processed,
                    'total' => $total,
                ], now()->addMinutes(60));
            }

            try {
                $subfolders = $drive->listChildFolders($actFolderId);
                $skaniFolderId = $subfolders['სკანი'] ?? null;

                if ($skaniFolderId === null) {
                    Log::info("DriveDownloadJob: ACT {$actNumber} has no სკანი subfolder — skipping");
                    $skipped++;
                } else {
                    $files = $drive->listFilesInFolder($skaniFolderId);

                    // Filter to PDFs only
                    $pdfs = array_values(array_filter(
                        $files,
                        fn (array $f) => ($f['mimeType'] ?? '') === 'application/pdf'
                    ));

                    if (empty($pdfs)) {
                        Log::info("DriveDownloadJob: ACT {$actNumber} სკანი has no PDF — skipping");
                        $skipped++;
                    } else {
                        // Enforce one-PDF-per-ACT rule
                        if (count($pdfs) > 1) {
                            $names = implode(', ', array_column($pdfs, 'name'));
                            Log::warning(
                                "DriveDownloadJob: ACT {$actNumber} has ".count($pdfs)." PDFs ({$names}) — taking first only"
                            );
                            $existing = Cache::get('download_skipped_files', []);
                            $existing[] = "ACT {$actNumber}: ".count($pdfs).' PDFs found — only first downloaded';
                            Cache::put('download_skipped_files', $existing, now()->addHours(2));
                        }

                        $file = $pdfs[0];

                        try {
                            $safeName = 'ACT_'.$actNumber.'_'.preg_replace('/[^\w.\-]/u', '_', $file['name']);
                            $content = $drive->downloadFile($file['id']);
                            $zip->addFromString($safeName, $content);
                            $zip->setCompressionName($safeName, ZipArchive::CM_STORE);
                            $downloaded++;
                        } catch (\Throwable $e) {
                            Log::warning(
                                "DriveDownloadJob: failed to download [{$file['id']}] for ACT {$actNumber} — ".$e->getMessage()
                            );
                            $failed++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("DriveDownloadJob: failed ACT {$actNumber} — ".$e->getMessage());
                $failed++;
            }

            // Store checkpoint after each successfully processed ACT
            Cache::put("download_scan_checkpoint:{$slug}", $actFolderId, now()->addHours(4));
        }

        $zip->close();

        // ── Final progress update ────────────────────────────────────────
        Cache::put("download_scan_progress:{$slug}", [
            'done' => $total,
            'total' => $total,
        ], now()->addMinutes(60));

        Log::info(
            "DriveDownloadJob [{$label}]: done — downloaded: {$downloaded}, skipped: {$skipped}, failed: {$failed}"
        );

        // ── Store result or clean up empty ZIP ───────────────────────────
        if ($downloaded > 0) {
            $zipName = "scans-{$slug}-{$timestamp}.zip";
            Cache::put("download_scan_ready_path:{$slug}", $zipPath, now()->addHours(24));
            Cache::put("download_scan_ready_name:{$slug}", $zipName, now()->addHours(24));

            $skippedFiles = Cache::get('download_skipped_files', []);
            if (! empty($skippedFiles)) {
                Cache::put("download_scan_skipped_warnings:{$slug}", $skippedFiles, now()->addHours(24));
            }
        } else {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }

        // ── Clean up run state ───────────────────────────────────────────
        Cache::forget('download_skipped_files');
        Cache::forget("download_scan_run_id:{$slug}");
        Cache::forget("download_scan_checkpoint:{$slug}");
        Cache::forget("download_scan_zip_path:{$slug}:{$runId}");
        Cache::forget("download_scan_running:{$slug}");
        Cache::forget("download_scan_progress:{$slug}");

        // ── Telegram notification ─────────────────────────────────────────
        $downloadUrl = rtrim(config('app.url'), '/').'/dashboard/download/file?range='.urlencode($slug);
        $telegram->sendToGroup(
            "📦 სკანების გადმოწერა დასრულდა [{$label}]\n\n".
                "✅ ჩამოწერილია: {$downloaded}\n".
                "⏭️ სკანი არ აქვს: {$skipped}\n".
                ($failed > 0 ? "❌ შეცდომა: {$failed}\n" : '').
                "\n⬇️ {$downloadUrl}"
        );
    }
}
