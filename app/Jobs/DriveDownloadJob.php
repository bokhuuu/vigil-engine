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

        // folder_index: folderId => ['act_number' => ..., 'act_record_id' => ...]
        foreach ($folderIndex as $actFolderId => $actInfo) {
            $actNumber = $actInfo['act_number'];

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
                        $content = $drive->downloadFile($file['id']);
                        $safeName = preg_replace('/[^\w.\-]/u', '_', $file['name']);
                        $zip->addFromString("{$actNumber}/{$safeName}", $content);
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
        } else {
            // Nothing downloaded — remove the empty ZIP
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }

        $downloadUrl = rtrim(config('app.url'), '/').'/dashboard/download/file';
        $telegram->send(
            config('vigil.telegram.group_chat_id'),
            "📦 სკანების გადმოწერა დასრულდა\n\n".
                "✅ ჩამოწერილია: {$downloaded}\n".
                "⏭️ სკანი არ აქვს: {$skipped}\n".
                ($failed > 0 ? "❌ შეცდომა: {$failed}\n" : '').
                "\n⬇️ {$downloadUrl}"
        );

        Cache::forget('download_running');
    }
}
