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

class DrivePhotosUploadJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'heic', 'heif', 'webp', 'gif', 'tiff', 'tif', 'bmp'];

    private const MIME_MAP = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'heic' => 'image/heic',
        'heif' => 'image/heif',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'bmp' => 'image/bmp',
    ];

    public function __construct(
        public readonly ?string $localZipPath = null,
    ) {}

    public function handle(
        DriveWatcherStateRepository $stateRepo,
        GoogleDriveService $drive,
        TelegramService $telegram,
    ): void {
        Log::info('DrivePhotosUploadJob: starting');

        $stagingFolderId = config('services.google_drive.staging.photos');
        $folderIndex = $stateRepo->getFolderIndex();

        if (empty($folderIndex)) {
            Log::warning('DrivePhotosUploadJob: folder_index is empty — aborting');
            $telegram->sendToGroup('⚠️ ფოტოების ატვირთვა: folder_index ცარიელია');
            Cache::forget('upload_photos_running');

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

        // Build ZIP list — either local upload or Drive staging
        if ($this->localZipPath !== null) {
            $zips = [['id' => null, 'name' => basename($this->localZipPath), 'localPath' => $this->localZipPath]];
        } else {
            $stagingFiles = $drive->listFilesInFolder($stagingFolderId);

            $zips = array_values(array_filter(
                $stagingFiles,
                fn (array $f) => strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) === 'zip'
            ));

            $nonZips = array_values(array_filter(
                $stagingFiles,
                fn (array $f) => strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'zip'
            ));

            foreach ($nonZips as $f) {
                Log::warning("DrivePhotosUploadJob: unexpected non-ZIP file in staging: {$f['name']}");
                $telegram->sendToGroup("⚠️ სტეიჯინგში მოულოდნელი ფაილი (არ არის ZIP): {$f['name']}");
            }

            if (empty($zips)) {
                Log::info('DrivePhotosUploadJob: no ZIP files found in staging — aborting');
                $telegram->sendToGroup('⚠️ ფოტოების ატვირთვა: სტეიჯინგში ZIP ფაილი არ არის');
                Cache::forget('upload_photos_running');

                return;
            }
        }

        $totalUploaded = 0;
        $totalSkippedExists = 0;
        $totalSkippedNoAct = 0;
        $totalFailed = 0;

        foreach ($zips as $zipFile) {
            Log::info("DrivePhotosUploadJob: processing ZIP [{$zipFile['name']}]");

            // Use local path if uploaded directly, otherwise download from Drive
            if (isset($zipFile['localPath'])) {
                $tmpZip = $zipFile['localPath'];
                $deleteTmp = false;
            } else {
                $tmpZip = tempnam(sys_get_temp_dir(), 'vigil_photos_upload_');
                $deleteTmp = true;

                try {
                    $drive->downloadFileTo($zipFile['id'], $tmpZip);
                } catch (\Throwable $e) {
                    Log::error("DrivePhotosUploadJob: failed to download ZIP [{$zipFile['name']}] — ".$e->getMessage());
                    $telegram->sendToGroup("❌ ZIP გადმოწერა ვერ მოხდა: {$zipFile['name']}");
                    @unlink($tmpZip);

                    continue;
                }
            }

            $zip = new \ZipArchive;

            if ($zip->open($tmpZip) !== true) {
                Log::error("DrivePhotosUploadJob: cannot open ZIP [{$tmpZip}]");
                @unlink($tmpZip);

                continue;
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $filename = $entry['name'] ?? '';

                // Skip directories
                if (str_ends_with($filename, '/')) {
                    continue;
                }

                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                // Skip non-image files
                if (! in_array($ext, self::IMAGE_EXTENSIONS, true)) {
                    Log::info("DrivePhotosUploadJob: skipping non-image entry [{$filename}]");

                    continue;
                }

                $basename = basename($filename);

                // Extract ACT number from filename.
                // Step 1: strip anything in parentheses (e.g. copy markers like "(2)")
                // Step 2: grab first digit sequence, optionally with a dash (e.g. 505 or 505-1)
                $cleaned = preg_replace('/\([^)]*\)/', '', $filename) ?? $filename;

                if (! preg_match('/(\d+(?:-\d+)?)/', $cleaned, $matches)) {
                    Log::warning("DrivePhotosUploadJob: cannot extract ACT number from [{$basename}]");
                    $totalSkippedNoAct++;

                    continue;
                }

                $actNumber = $matches[1];

                if (! isset($actMap[$actNumber])) {
                    Log::warning("DrivePhotosUploadJob: ACT {$actNumber} not found in folder_index — skipping");
                    $totalSkippedNoAct++;

                    continue;
                }

                $actFolderId = $actMap[$actNumber];

                try {
                    // Find or create ფოტოები subfolder
                    $subfolders = $drive->listChildFolders($actFolderId);
                    $photosFolderId = $subfolders['ფოტოები'] ?? $drive->findOrCreateFolder('ფოტოები', $actFolderId);

                    // Skip mode: check if a file with the same name already exists
                    $existing = $drive->listFilesInFolder($photosFolderId);
                    $existingNames = array_column($existing, 'name');

                    if (in_array($basename, $existingNames, true)) {
                        Log::info("DrivePhotosUploadJob: [{$basename}] already exists in ACT {$actNumber} ფოტოები — skipping");
                        $totalSkippedExists++;

                        continue;
                    }

                    // Read file content from ZIP
                    $content = $zip->getFromIndex($i);

                    if ($content === false) {
                        Log::warning("DrivePhotosUploadJob: cannot read ZIP entry [{$basename}]");
                        $totalFailed++;

                        continue;
                    }

                    if (strlen($content) > 50 * 1024 * 1024) {
                        Log::warning("DrivePhotosUploadJob: [{$basename}] too large (".round(strlen($content) / 1024 / 1024, 1).'MB) — skipping');
                        $totalFailed++;

                        continue;
                    }

                    $mimeType = self::MIME_MAP[$ext] ?? 'application/octet-stream';

                    // Upload via OAuth
                    $drive->uploadFile($photosFolderId, $basename, $content, $mimeType);
                    $totalUploaded++;

                    Log::info("DrivePhotosUploadJob: uploaded [{$basename}] to ACT {$actNumber}");
                } catch (\Throwable $e) {
                    Log::warning("DrivePhotosUploadJob: failed [{$basename}] ACT {$actNumber} — ".$e->getMessage());
                    $totalFailed++;
                }
            }

            $zip->close();

            if ($deleteTmp) {
                @unlink($tmpZip);

                try {
                    $drive->trashFile($zipFile['id']);
                    Log::info("DrivePhotosUploadJob: trashed staging ZIP [{$zipFile['name']}]");
                } catch (\Throwable $e) {
                    Log::warning("DrivePhotosUploadJob: could not trash staging ZIP [{$zipFile['name']}] — ".$e->getMessage());
                }
            } else {
                @unlink($tmpZip);
            }
        }

        Log::info(
            "DrivePhotosUploadJob: done — uploaded: {$totalUploaded}, ".
                "skipped_exists: {$totalSkippedExists}, skipped_no_act: {$totalSkippedNoAct}, failed: {$totalFailed}"
        );

        $telegram->sendToGroup(
            "📸 ფოტოების ატვირთვა დასრულდა [⏭️ skip-if-exists]\n\n".
                "✅ ატვირთულია: {$totalUploaded}\n".
                ($totalSkippedExists > 0 ? "⏭️ უკვე არსებობს: {$totalSkippedExists}\n" : '').
                ($totalSkippedNoAct > 0 ? "🔍 ACT ვერ მოიძებნა: {$totalSkippedNoAct}\n" : '').
                ($totalFailed > 0 ? "❌ შეცდომა: {$totalFailed}\n" : '')
        );

        Cache::forget('upload_photos_running');
    }
}
