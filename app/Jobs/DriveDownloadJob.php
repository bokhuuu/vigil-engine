<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\DriveWatcherStateRepository;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class DriveDownloadJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function handle(
        DriveWatcherStateRepository $stateRepo,
        GoogleDriveService $drive,
        TelegramService $telegram,
    ): void {
        Log::info('DriveDownloadJob: starting სკანი download');

        $folderIndex = $stateRepo->getFolderIndex();

        $total = count($folderIndex);

        if (empty($folderIndex)) {
            Log::warning('DriveDownloadJob: folder_index is empty — aborting');
            Cache::forget('download_running');

            return;
        }

        // Prepare ZIP
        $timestamp = now()->format('Y-m-d_His');
        $downloadsDir = storage_path('app/downloads');

        if (! is_dir($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        $zipPath = "{$downloadsDir}/scans-{$timestamp}.zip";
        $zip = new ZipArchive;

        $zipFlags = ZipArchive::CREATE | ZipArchive::OVERWRITE;

        if ($zip->open($zipPath, $zipFlags) !== true) {
            Log::error("DriveDownloadJob: cannot create ZIP at {$zipPath}");
            Cache::forget('download_running');

            return;
        }

        $downloaded = 0;
        $skipped = 0;
        $failed = 0;

        Cache::forget('download_skipped_files');
        Cache::forget('download_skipped_warnings');

        $processed = 0;

        foreach ($folderIndex as $actFolderId => $actInfo) {
            $actNumber = $actInfo['act_number'];
            $processed++;

            if ($processed % 5 === 0 || $processed === 1) {
                Cache::put('download_progress', [
                    'done' => $processed,
                    'total' => $total,
                ], now()->addMinutes(60));
            }

            try {
                $subfolders = $drive->listChildFolders($actFolderId);
                $skaniFolderId = $subfolders['სკანი'] ?? null;

                if ($skaniFolderId === null) {
                    $skipped++;

                    continue;
                }

                $files = $drive->listFilesInFolder($skaniFolderId);

                foreach ($files as $file) {
                    try {
                        $mimeType = $file['mimeType'] ?? '';
                        $safeName = preg_replace('/[^\w.\-]/u', '_', $file['name']);
                        if ($mimeType !== 'application/pdf') {
                            Log::warning("DriveDownloadJob: skipping non-PDF [{$file['name']}] in ACT {$actNumber}");
                            $existing = Cache::get('download_skipped_files', []);
                            $existing[] = "ACT {$actNumber}: {$file['name']} ({$mimeType})";
                            Cache::put('download_skipped_files', $existing, now()->addHours(2));
                            $skipped++;

                            continue;
                        }
                        $content = $drive->downloadFile($file['id']);
                        $zip->addFromString($safeName, $content);
                        $zip->setCompressionName($safeName, ZipArchive::CM_STORE);
                        $downloaded++;
                    } catch (\Throwable $e) {
                        Log::warning("DriveDownloadJob: failed to download [{$file['id']}] for ACT {$actNumber} — ".$e->getMessage());
                        $failed++;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("DriveDownloadJob: failed ACT {$actNumber} — ".$e->getMessage());
                $failed++;
            }
        }

        $zip->close();

        Log::info("DriveDownloadJob: done — downloaded: {$downloaded}, skipped: {$skipped}, failed: {$failed}");

        if ($downloaded > 0) {
            Cache::put('download_ready_path', $zipPath, now()->addHours(24));
            Cache::put('download_ready_name', "scans-{$timestamp}.zip", now()->addHours(24));
            $skippedFiles = Cache::get('download_skipped_files', []);
            if (! empty($skippedFiles)) {
                Cache::put('download_skipped_warnings', $skippedFiles, now()->addHours(24));
            }
            Cache::forget('download_skipped_files');
        } else {
            // Nothing downloaded — remove the empty ZIP
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }

        $downloadUrl = rtrim(config('app.url'), '/').'/dashboard/download/file';
        $telegram->sendToGroup(
            "📦 სკანების გადმოწერა დასრულდა\n\n".
                "✅ ჩამოწერილია: {$downloaded}\n".
                "⏭️ სკანი არ აქვს: {$skipped}\n".
                ($failed > 0 ? "❌ შეცდომა: {$failed}\n" : '').
                "\n⬇️ {$downloadUrl}"
        );
        Cache::forget('download_running');
        Cache::forget('download_progress');
    }
}
