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

    public int $timeout = 7200;

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
        if (Cache::has('upload_photos_running')) {
            Log::warning('DrivePhotosUploadJob: already running, aborting duplicate');

            return;
        }

        Cache::put('upload_photos_running', true, 7200);

        try {
            Log::info('DrivePhotosUploadJob: starting');

            $stagingFolderId = config('services.google_drive.staging.photos');
            $folderIndex = $stateRepo->getFolderIndex();

            if (empty($folderIndex)) {
                Log::warning('DrivePhotosUploadJob: folder_index is empty — aborting');
                $telegram->sendToGroup('⚠️ ფოტოების ატვირთვა: folder_index ცარიელია');

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

                    return;
                }
            }

            $totalUploaded = 0;
            $totalSkippedExists = 0;
            $totalSkippedNoAct = 0;
            $skippedNoActNumbers = [];
            $totalFailed = 0;
            $uploadedSubfolderIds = [];
            $existingNamesCache = [];

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

                    // Extract ACT number from the TOP-LEVEL folder name only.
                    // e.g. "აქტი619-1/606-თან/photo.jpg" → top segment "აქტი619-1" → ACT "619-1"
                    $segments = explode('/', $filename);
                    $topFolder = $segments[0];

                    // Build the relative path inside ფოტოები, preserving subfolders.
                    // e.g. "აქტი619-1/606-თან/photo.jpg" → relativeDir = "606-თან"
                    // e.g. "აქტი619-1/photo.jpg"         → relativeDir = "" (upload directly to ფოტოები)
                    $innerSegments = array_slice($segments, 1); // remove top folder
                    // Filter out 'ფოტოები' — ZIP may include it as a subfolder name but Drive already creates it as root
                    $innerSegments = array_values(array_filter(
                        $innerSegments,
                        fn (string $s) => $s !== 'ფოტოები'
                    ));
                    $relativeDir = count($innerSegments) > 1
                        ? implode('/', array_slice($innerSegments, 0, -1)) // all segments except filename
                        : '';

                    // Strip anything in parentheses, then grab only the first digit sequence (no dash suffix)
                    $cleanedTop = preg_replace('/\([^)]*\)/', '', $topFolder) ?? $topFolder;

                    if (! preg_match('/(\d+(?:-\d+)?)/', $cleanedTop, $matches)) {
                        Log::warning("DrivePhotosUploadJob: cannot extract ACT number from top folder [{$topFolder}]");
                        $totalSkippedNoAct++;
                        $skippedNoActNumbers[] = $actNumber ?? $topFolder;

                        continue;
                    }

                    $actNumber = $matches[1];

                    if (! isset($actMap[$actNumber])) {
                        Log::warning("DrivePhotosUploadJob: ACT {$actNumber} not found in folder_index — skipping [{$topFolder}]");
                        $totalSkippedNoAct++;
                        $skippedNoActNumbers[] = $actNumber ?? $topFolder;

                        continue;
                    }

                    $actFolderId = $actMap[$actNumber];

                    try {
                        // Find or create ფოტოები subfolder
                        $subfolders = $drive->listChildFolders($actFolderId);
                        $photosFolderId = $subfolders['ფოტოები'] ?? $drive->findOrCreateFolder('ფოტოები', $actFolderId);

                        // Resolve the exact target folder (preserving subfolder structure from ZIP)
                        // e.g. relativeDir = "606-თან" → find/create ფოტოები/606-თან
                        // e.g. relativeDir = ""         → upload directly to ფოტოები
                        if ($relativeDir !== '') {
                            $targetFolderId = $drive->ensureSubfolderPathFromId($photosFolderId, $relativeDir);
                        } else {
                            $targetFolderId = $photosFolderId;
                        }

                        // Skip mode: check if a file with the same name already exists in the target folder
                        // Cache per folder — one API call per folder, not per file
                        if (! isset($existingNamesCache[$targetFolderId])) {
                            $subfolderState = $stateRepo->getSubfolderState($targetFolderId);
                            if ($subfolderState === 'empty' || $subfolderState === null) {
                                // Folder is known empty — skip the API call
                                $existingNamesCache[$targetFolderId] = [];
                            } else {
                                $existingNamesCache[$targetFolderId] = array_column(
                                    $drive->listFilesInFolder($targetFolderId),
                                    'name'
                                );
                            }
                        }
                        $existingNames = $existingNamesCache[$targetFolderId];

                        if (in_array($basename, $existingNames, true)) {
                            Log::info("DrivePhotosUploadJob: [{$basename}] already exists in ACT {$actNumber}/{$relativeDir} — skipping");
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

                        // Upload via OAuth into the correct (sub)folder
                        $drive->uploadFile($targetFolderId, $basename, $content, $mimeType);
                        $totalUploaded++;
                        $existingNamesCache[$targetFolderId][] = $basename; // keep cache fresh
                        $uploadedSubfolderIds[$photosFolderId] = true; // always track root ფოტოები

                        Log::info("DrivePhotosUploadJob: uploaded [{$basename}] to ACT {$actNumber}/{$relativeDir}");
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

            foreach (array_keys($uploadedSubfolderIds) as $uploadedFolderId) {
                try {
                    $stateRepo->setSubfolderState($uploadedFolderId, 'full');
                    Log::info("DrivePhotosUploadJob: marked subfolder [{$uploadedFolderId}] as full in state");
                } catch (\Throwable $e) {
                    Log::warning("DrivePhotosUploadJob: could not update state for [{$uploadedFolderId}] — ".$e->getMessage());
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
                    ($totalSkippedNoAct > 0 ? "🔍 ACT ვერ მოიძებნა: {$totalSkippedNoAct}".(count($skippedNoActNumbers) ? ' ('.implode(', ', array_unique($skippedNoActNumbers)).')' : '')."\n" : '').
                    ($totalFailed > 0 ? "❌ შეცდომა: {$totalFailed}\n" : '')
            );
        } catch (\Throwable $e) {
            Log::error('DrivePhotosUploadJob: unexpected failure — '.$e->getMessage(), [
                'exception' => $e,
            ]);

            $telegram->sendToGroup(
                "🔴 ფოტოების ატვირთვა — კრიტიკული შეცდომა\n\n".
                    '❌ '.$e->getMessage()
            );
        } finally {
            Cache::forget('upload_photos_running');
        }
    }
}
