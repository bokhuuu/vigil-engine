<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\DriveWatcherStateRepository;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DriveScansUploadJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(
        public readonly string $mode = 'skip', // 'skip' | 'replace'
    ) {}

    public function handle(
        DriveWatcherStateRepository $stateRepo,
        GoogleDriveService $drive,
        TelegramService $telegram,
    ): void {
        Log::info("DriveScansUploadJob: starting [mode={$this->mode}]");

        $stagingFolderId = config('services.google_drive.staging.scans');
        $folderIndex = $stateRepo->getFolderIndex();

        if (empty($folderIndex)) {
            Log::warning('DriveScansUploadJob: folder_index is empty — aborting');
            $telegram->sendToGroup('⚠️ სკანების ატვირთვა: folder_index ცარიელია');

            return;
        }

        // Build ACT number → folder ID lookup map
        $actMap = [];
        foreach ($folderIndex as $folderId => $info) {
            $actNumber = (string) ($info['act_number'] ?? '');
            if ($actNumber !== '') {
                $actMap[$actNumber] = $folderId;
            }
        }

        // List files in staging/scans/
        $stagingFiles = $drive->listFilesInFolder($stagingFolderId);

        $zips = array_values(array_filter(
            $stagingFiles,
            fn (array $f) => strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) === 'zip'
        ));

        $nonZips = array_values(array_filter(
            $stagingFiles,
            fn (array $f) => strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'zip'
        ));

        // Alert about unexpected non-ZIP files
        foreach ($nonZips as $f) {
            Log::warning("DriveScansUploadJob: unexpected non-ZIP file in staging: {$f['name']}");
            $telegram->sendToGroup("⚠️ სტეიჯინგში მოულოდნელი ფაილი (არ არის ZIP): {$f['name']}");
        }

        if (empty($zips)) {
            Log::info('DriveScansUploadJob: no ZIP files found in staging — aborting');
            $telegram->sendToGroup('⚠️ სკანების ატვირთვა: სტეიჯინგში ZIP ფაილი არ არის');

            return;
        }

        $totalUploaded = 0;
        $totalSkippedExists = 0;
        $totalSkippedNoAct = 0;
        $totalFailed = 0;

        foreach ($zips as $zipFile) {
            Log::info("DriveScansUploadJob: processing ZIP [{$zipFile['name']}]");

            // Download ZIP to temp file
            $tmpZip = tempnam(sys_get_temp_dir(), 'vigil_scans_upload_');

            try {
                $drive->downloadFileTo($zipFile['id'], $tmpZip);
            } catch (\Throwable $e) {
                Log::error("DriveScansUploadJob: failed to download ZIP [{$zipFile['name']}] — ".$e->getMessage());
                $telegram->sendToGroup("❌ ZIP გადმოწერა ვერ მოხდა: {$zipFile['name']}");
                @unlink($tmpZip);

                continue;
            }

            $zip = new \ZipArchive;

            if ($zip->open($tmpZip) !== true) {
                Log::error("DriveScansUploadJob: cannot open ZIP [{$tmpZip}]");
                @unlink($tmpZip);

                continue;
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $filename = $entry['name'] ?? '';

                // Skip directories and non-PDFs
                if (str_ends_with($filename, '/')) {
                    continue;
                }

                if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'pdf') {
                    Log::info("DriveScansUploadJob: skipping non-PDF entry [{$filename}]");

                    continue;
                }

                $basename = basename($filename);

                // Extract ACT number from filename: სკანი_848.pdf or სკანი_619-1.pdf
                if (! preg_match('/(\d+(?:-\d+)?)\.[Pp][Dd][Ff]$/u', $basename, $matches)) {
                    Log::warning("DriveScansUploadJob: cannot extract ACT number from [{$basename}]");
                    $totalSkippedNoAct++;

                    continue;
                }

                $actNumber = $matches[1];

                if (! isset($actMap[$actNumber])) {
                    Log::warning("DriveScansUploadJob: ACT {$actNumber} not found in folder_index — skipping");
                    $totalSkippedNoAct++;

                    continue;
                }

                $actFolderId = $actMap[$actNumber];

                try {
                    // Find or create სკანი subfolder
                    $subfolders = $drive->listChildFolders($actFolderId);
                    $skaniFolderId = $subfolders['სკანი'] ?? $drive->findOrCreateFolder('სკანი', $actFolderId);

                    if ($this->mode === 'replace') {
                        // Trash all existing files in სკანი
                        $drive->trashFolderChildren($skaniFolderId);
                    } else {
                        // skip mode: check if any file with the same name already exists
                        $existing = $drive->listFilesInFolder($skaniFolderId);
                        $existingNames = array_column($existing, 'name');

                        if (in_array($basename, $existingNames, true)) {
                            Log::info("DriveScansUploadJob: [{$basename}] already exists in ACT {$actNumber} სკანი — skipping");
                            $totalSkippedExists++;

                            continue;
                        }
                    }

                    // Read file content from ZIP
                    $content = $zip->getFromIndex($i);

                    if ($content === false) {
                        Log::warning("DriveScansUploadJob: cannot read ZIP entry [{$basename}]");
                        $totalFailed++;

                        continue;
                    }

                    // Upload via service account
                    $drive->uploadFileAsServiceAccount($skaniFolderId, $basename, $content, 'application/pdf');
                    $totalUploaded++;

                    Log::info("DriveScansUploadJob: uploaded [{$basename}] to ACT {$actNumber}");
                } catch (\Throwable $e) {
                    Log::warning("DriveScansUploadJob: failed [{$basename}] ACT {$actNumber} — ".$e->getMessage());
                    $totalFailed++;
                }
            }

            $zip->close();
            @unlink($tmpZip);

            // Trash the processed ZIP from staging
            try {
                $drive->trashFile($zipFile['id']);
                Log::info("DriveScansUploadJob: trashed staging ZIP [{$zipFile['name']}]");
            } catch (\Throwable $e) {
                Log::warning("DriveScansUploadJob: could not trash staging ZIP [{$zipFile['name']}] — ".$e->getMessage());
            }
        }

        Log::info(
            "DriveScansUploadJob: done — uploaded: {$totalUploaded}, ".
                "skipped_exists: {$totalSkippedExists}, skipped_no_act: {$totalSkippedNoAct}, failed: {$totalFailed}"
        );

        $modeLabel = $this->mode === 'replace' ? '🔄 replace' : '⏭️ skip-if-exists';

        $telegram->sendToGroup(
            "📤 სკანების ატვირთვა დასრულდა [{$modeLabel}]\n\n".
                "✅ ატვირთულია: {$totalUploaded}\n".
                ($totalSkippedExists > 0 ? "⏭️ უკვე არსებობს: {$totalSkippedExists}\n" : '').
                ($totalSkippedNoAct > 0 ? "🔍 ACT ვერ მოიძებნა: {$totalSkippedNoAct}\n" : '').
                ($totalFailed > 0 ? "❌ შეცდომა: {$totalFailed}\n" : '')
        );
    }
}
