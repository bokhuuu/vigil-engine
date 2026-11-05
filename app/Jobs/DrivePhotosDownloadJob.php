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

class DrivePhotosDownloadJob implements ShouldQueue
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

        Log::info("DrivePhotosDownloadJob: starting ფოტოები download [{$label}]");

        $allFolderIndex = $stateRepo->getFolderIndex();

        if (empty($allFolderIndex)) {
            Log::warning('DrivePhotosDownloadJob: folder_index is empty — aborting');
            Cache::forget("download_photos_running:{$slug}");

            return;
        }

        $folderIndex = ActRangeHelper::filterFolderIndex($allFolderIndex, $this->actRange);
        $total = count($folderIndex);

        if ($total === 0) {
            Log::warning("DrivePhotosDownloadJob: no ACTs in range [{$label}] — aborting");
            Cache::forget("download_photos_running:{$slug}");

            return;
        }

        // ── Checkpoint / resume logic ────────────────────────────────────
        $existingRunId = Cache::get("download_photos_run_id:{$slug}");
        $checkpoint = Cache::get("download_photos_checkpoint:{$slug}");
        $isResume = $existingRunId !== null && $checkpoint !== null;

        if ($isResume) {
            $runId = $existingRunId;
            Log::info("DrivePhotosDownloadJob: resuming run {$runId} from checkpoint {$checkpoint}");
        } else {
            $runId = Str::uuid()->toString();
            Cache::put("download_photos_run_id:{$slug}", $runId, now()->addHours(4));
            Cache::forget("download_photos_checkpoint:{$slug}");
            Log::info("DrivePhotosDownloadJob: new run {$runId}");
        }

        // ── ZIP setup ────────────────────────────────────────────────────
        $timestamp = now()->format('Y-m-d_His');
        $downloadsDir = storage_path('app/downloads');

        if (! is_dir($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        $zipPath = Cache::get("download_photos_zip_path:{$slug}:{$runId}")
            ?? "{$downloadsDir}/photos-{$slug}-{$timestamp}.zip";

        Cache::put("download_photos_zip_path:{$slug}:{$runId}", $zipPath, now()->addHours(4));

        $zip = new ZipArchive;
        $zipFlags = ($isResume && file_exists($zipPath))
            ? ZipArchive::CREATE
            : ZipArchive::CREATE | ZipArchive::OVERWRITE;

        if ($zip->open($zipPath, $zipFlags) !== true) {
            Log::error("DrivePhotosDownloadJob: cannot open ZIP at {$zipPath}");
            Cache::forget("download_photos_running:{$slug}");

            return;
        }

        // ── Process ACTs ─────────────────────────────────────────────────
        $downloaded = 0;
        $skipped = 0;
        $failed = 0;
        $processed = 0;
        $pastCheckpoint = ! $isResume;
        $tmpFiles = [];

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
                Cache::put("download_photos_progress:{$slug}", [
                    'done' => $processed,
                    'total' => $total,
                ], now()->addMinutes(60));
            }

            try {
                $subfolders = $drive->listChildFolders($actFolderId);
                $photosFolderId = $subfolders['ფოტოები'] ?? null;

                if ($photosFolderId === null) {
                    Log::info("DrivePhotosDownloadJob: ACT {$actNumber} has no ფოტოები subfolder — skipping");
                    $skipped++;
                } else {
                    // Recursive listing — preserves nested folder structure
                    $allFiles = $drive->listAllInFolder($photosFolderId);

                    // Accept any image/* MIME type
                    $images = array_values(array_filter(
                        $allFiles,
                        fn (array $f) => str_starts_with((string) ($f['mimeType'] ?? ''), 'image/')
                    ));

                    if (empty($images)) {
                        Log::info("DrivePhotosDownloadJob: ACT {$actNumber} ფოტოები has no images — skipping");
                        $skipped++;
                    } else {
                        foreach ($images as $file) {
                            try {
                                // Nested path: ACT_123/subfolder/image.jpg
                                $safePath = 'ACT_'.$actNumber.'/'.preg_replace('/[^\w.\-\/]/u', '_', $file['path']);
                                $tmpPath = sys_get_temp_dir().'/vigil_photo_'.uniqid('', true);

                                $drive->downloadFileTo($file['id'], $tmpPath);
                                $zip->addFile($tmpPath, $safePath);
                                $zip->setCompressionName($safePath, ZipArchive::CM_STORE);
                                $tmpFiles[] = $tmpPath;
                                $downloaded++;
                            } catch (\Throwable $e) {
                                Log::warning(
                                    "DrivePhotosDownloadJob: failed to download [{$file['id']}] for ACT {$actNumber} — ".$e->getMessage()
                                );
                                $failed++;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("DrivePhotosDownloadJob: failed ACT {$actNumber} — ".$e->getMessage());
                $failed++;
            }

            // Store checkpoint after each processed ACT
            Cache::put("download_photos_checkpoint:{$slug}", $actFolderId, now()->addHours(4));
        }

        $zip->close();

        // Clean up temp files used by addFile() — must happen AFTER close()
        foreach ($tmpFiles as $tmp) {
            if (file_exists($tmp)) {
                unlink($tmp);
            }
        }

        // ── Final progress update ────────────────────────────────────────
        Cache::put("download_photos_progress:{$slug}", [
            'done' => $total,
            'total' => $total,
        ], now()->addMinutes(60));

        Log::info(
            "DrivePhotosDownloadJob [{$label}]: done — downloaded: {$downloaded}, skipped: {$skipped}, failed: {$failed}"
        );

        // ── Store result or clean up empty ZIP ───────────────────────────
        if ($downloaded > 0) {
            $zipName = "photos-{$slug}-{$timestamp}.zip";
            Cache::put("download_photos_ready_path:{$slug}", $zipPath, now()->addHours(24));
            Cache::put("download_photos_ready_name:{$slug}", $zipName, now()->addHours(24));
        } else {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }

        // ── Clean up run state ───────────────────────────────────────────
        Cache::forget("download_photos_run_id:{$slug}");
        Cache::forget("download_photos_checkpoint:{$slug}");
        Cache::forget("download_photos_zip_path:{$slug}:{$runId}");
        Cache::forget("download_photos_running:{$slug}");
        Cache::forget("download_photos_progress:{$slug}");

        // ── Telegram notification ─────────────────────────────────────────
        $downloadUrl = rtrim(config('app.url'), '/').'/dashboard/download/photos?range='.urlencode($slug);
        $telegram->sendToGroup(
            "📸 ფოტოების გადმოწერა დასრულდა [{$label}]\n\n".
                "✅ ჩამოწერილია: {$downloaded}\n".
                "⏭️ ფოტოები არ აქვს: {$skipped}\n".
                ($failed > 0 ? "❌ შეცდომა: {$failed}\n" : '').
                "\n⬇️ {$downloadUrl}"
        );
    }
}
