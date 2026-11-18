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

class DriveDrawingsUploadJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 7200;

    public int $tries = 1;

    private const DRAWINGS_FOLDER = 'შიდა ნახაზები';

    public function __construct(
        public readonly string $mode,
        public readonly ?string $localZipPath = null,
    ) {}

    public function handle(
        DriveWatcherStateRepository $stateRepo,
        GoogleDriveService $drive,
        TelegramService $telegram,
    ): void {
        try {
            Log::info("DriveDrawingsUploadJob: starting [mode={$this->mode}]");

            $folderIndex = $stateRepo->getFolderIndex();

            if (empty($folderIndex)) {
                Log::warning('DriveDrawingsUploadJob: folder_index is empty — aborting');
                $telegram->sendToGroup('⚠️ ნახაზების ატვირთვა: folder_index ცარიელია');

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

            // Build ZIP list — local upload only
            if ($this->localZipPath !== null) {
                $zips = [['id' => null, 'name' => basename($this->localZipPath), 'localPath' => $this->localZipPath]];
            } else {
                Log::warning('DriveDrawingsUploadJob: no localZipPath provided — aborting');
                $telegram->sendToGroup('⚠️ ნახაზების ატვირთვა: ZIP ფაილი არ მოწოდებულა');

                return;
            }

            $totalUploaded = 0;
            $totalSkippedExists = 0;
            $totalSkippedNoAct = 0;
            $skippedNoActNumbers = [];
            $totalReplaced = 0;
            $totalFailed = 0;
            $uploadedDrawingsFolderIds = [];
            $existingNamesCache = [];

            foreach ($zips as $zipFile) {
                Log::info("DriveDrawingsUploadJob: processing ZIP [{$zipFile['name']}]");

                $tmpZip = $zipFile['localPath'];

                $zip = new \ZipArchive;

                if ($zip->open($tmpZip) !== true) {
                    Log::error("DriveDrawingsUploadJob: cannot open ZIP [{$tmpZip}]");

                    continue;
                }

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->statIndex($i);
                    $filename = $entry['name'] ?? '';

                    // Skip directory entries
                    if (str_ends_with($filename, '/')) {
                        continue;
                    }

                    // Skip hidden/system files
                    $basename = basename($filename);
                    if (str_starts_with($basename, '.') || $basename === 'Thumbs.db' || $basename === 'desktop.ini') {
                        Log::info("DriveDrawingsUploadJob: skipping system file [{$filename}]");

                        continue;
                    }

                    // Extract ACT number from the TOP-LEVEL folder name only.
                    // e.g. "123-4/სქემები/drawing.dwg" → top segment "123-4" → ACT "123-4"
                    // e.g. "აქტი619-1/drawing.pdf"      → top segment "აქტი619-1" → ACT "619-1"
                    $segments = explode('/', $filename);
                    $topFolder = $segments[0];

                    // Build the relative path inside შიდა ნახაზები, preserving subfolders.
                    // e.g. "123-4/სქემები/drawing.dwg"       → relativeDir = "სქემები"
                    // e.g. "123-4/drawing.dwg"               → relativeDir = "" (upload directly to შიდა ნახაზები)
                    // e.g. "123-4/სქემები/ქვე/drawing.dwg"   → relativeDir = "სქემები/ქვე"
                    $innerSegments = array_slice($segments, 1); // remove top folder

                    // Filter out შიდა ნახაზები — ZIP may include it as a subfolder but Drive already creates it as root
                    $innerSegments = array_values(array_filter(
                        $innerSegments,
                        fn (string $s) => $s !== self::DRAWINGS_FOLDER
                    ));

                    $relativeDir = count($innerSegments) > 1
                        ? implode('/', array_slice($innerSegments, 0, -1)) // all segments except filename
                        : '';

                    // Strip anything in parentheses, then grab the first digit sequence (with optional dash suffix)
                    $cleanedTop = preg_replace('/\([^)]*\)/', '', $topFolder) ?? $topFolder;

                    if (! preg_match('/(\d+(?:-\d+)?)/', $cleanedTop, $matches)) {
                        Log::warning("DriveDrawingsUploadJob: cannot extract ACT number from top folder [{$topFolder}]");
                        $totalSkippedNoAct++;
                        $skippedNoActNumbers[] = $actNumber ?? $topFolder;

                        continue;
                    }

                    $actNumber = $matches[1];

                    if (! isset($actMap[$actNumber])) {
                        Log::warning("DriveDrawingsUploadJob: ACT {$actNumber} not found in folder_index — skipping [{$topFolder}]");
                        $totalSkippedNoAct++;
                        $skippedNoActNumbers[] = $actNumber ?? $topFolder;

                        continue;
                    }

                    $actFolderId = $actMap[$actNumber];

                    try {
                        // Find or create შიდა ნახაზები subfolder
                        $subfolders = $drive->listChildFolders($actFolderId);
                        $drawingsFolderId = $subfolders[self::DRAWINGS_FOLDER] ?? $drive->findOrCreateFolder(self::DRAWINGS_FOLDER, $actFolderId);

                        // Resolve the exact target folder (preserving subfolder structure from ZIP)
                        if ($relativeDir !== '') {
                            $targetFolderId = $drive->ensureSubfolderPathFromId($drawingsFolderId, $relativeDir);
                        } else {
                            $targetFolderId = $drawingsFolderId;
                        }

                        // Build existing names cache for this folder (one API call per folder)
                        if (! isset($existingNamesCache[$targetFolderId])) {
                            $existingNamesCache[$targetFolderId] = array_column(
                                $drive->listFilesInFolder($targetFolderId),
                                'name'
                            );
                        }
                        $existingNames = $existingNamesCache[$targetFolderId];

                        if (in_array($basename, $existingNames, true)) {
                            if ($this->mode === 'replace') {
                                // Trash the existing file before re-uploading
                                $existingFiles = $drive->listFilesInFolder($targetFolderId);
                                foreach ($existingFiles as $existingFile) {
                                    if ($existingFile['name'] === $basename) {
                                        $drive->trashFile($existingFile['id']);
                                        break;
                                    }
                                }
                                // Remove from cache so the slot is free
                                $existingNamesCache[$targetFolderId] = array_values(
                                    array_filter($existingNames, fn (string $n) => $n !== $basename)
                                );
                            } else {
                                Log::info("DriveDrawingsUploadJob: [{$basename}] already exists in ACT {$actNumber}/{$relativeDir} — skipping");
                                $totalSkippedExists++;

                                continue;
                            }
                        }

                        // Read file content from ZIP
                        $content = $zip->getFromIndex($i);

                        if ($content === false) {
                            Log::warning("DriveDrawingsUploadJob: cannot read ZIP entry [{$basename}]");
                            $totalFailed++;

                            continue;
                        }

                        if (strlen($content) > 100 * 1024 * 1024) {
                            Log::warning("DriveDrawingsUploadJob: [{$basename}] too large (".round(strlen($content) / 1024 / 1024, 1).'MB) — skipping');
                            $totalFailed++;

                            continue;
                        }

                        // Detect MIME type from extension
                        $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                        $mimeType = $this->mimeForExtension($ext);

                        // Upload into the correct (sub)folder
                        $drive->uploadFile($targetFolderId, $basename, $content, $mimeType);

                        if ($this->mode === 'replace' && in_array($basename, $existingNames, true)) {
                            $totalReplaced++;
                        } else {
                            $totalUploaded++;
                        }

                        $existingNamesCache[$targetFolderId][] = $basename; // keep cache fresh
                        $uploadedDrawingsFolderIds[$drawingsFolderId] = true; // track root შიდა ნახაზები

                        Log::info("DriveDrawingsUploadJob: uploaded [{$basename}] to ACT {$actNumber}/{$relativeDir}");
                    } catch (\Throwable $e) {
                        Log::warning("DriveDrawingsUploadJob: failed [{$basename}] ACT {$actNumber} — ".$e->getMessage());
                        $totalFailed++;
                    }
                }

                $zip->close();

                // Delete the local temp file after processing
                @unlink($tmpZip);
            }

            foreach (array_keys($uploadedDrawingsFolderIds) as $uploadedFolderId) {
                try {
                    $stateRepo->setSubfolderState($uploadedFolderId, 'full');
                    Log::info("DriveDrawingsUploadJob: marked subfolder [{$uploadedFolderId}] as full in state");
                } catch (\Throwable $e) {
                    Log::warning("DriveDrawingsUploadJob: could not update state for [{$uploadedFolderId}] — ".$e->getMessage());
                }
            }

            $modeLabel = $this->mode === 'replace' ? '🔄 replace' : '⏭️ skip-if-exists';

            Log::info(
                "DriveDrawingsUploadJob: done — uploaded: {$totalUploaded}, replaced: {$totalReplaced}, ".
                    "skipped_exists: {$totalSkippedExists}, skipped_no_act: {$totalSkippedNoAct}, failed: {$totalFailed}"
            );

            $telegram->sendToGroup(
                "📐 ნახაზების ატვირთვა დასრულდა [{$modeLabel}]\n\n".
                    "✅ ატვირთულია: {$totalUploaded}\n".
                    ($totalReplaced > 0 ? "🔄 ჩანაცვლდა: {$totalReplaced}\n" : '').
                    ($totalSkippedExists > 0 ? "⏭️ უკვე არსებობს: {$totalSkippedExists}\n" : '').
                    ($totalSkippedNoAct > 0 ? "🔍 ACT ვერ მოიძებნა: {$totalSkippedNoAct}".(count($skippedNoActNumbers) ? ' ('.implode(', ', array_unique($skippedNoActNumbers)).')' : '')."\n" : '').
                    ($totalFailed > 0 ? "❌ შეცდომა: {$totalFailed}\n" : '')
            );
        } catch (\Throwable $e) {
            Log::error('DriveDrawingsUploadJob: unexpected failure — '.$e->getMessage(), [
                'exception' => $e,
            ]);

            $telegram->sendToGroup(
                "🔴 ნახაზების ატვირთვა — კრიტიკული შეცდომა\n\n".
                    '❌ '.$e->getMessage()
            );
        } finally {
            Cache::forget('upload_drawings_running');
        }
    }

    private function mimeForExtension(string $ext): string
    {
        return match ($ext) {
            'pdf' => 'application/pdf',
            'dwg' => 'application/acad',
            'dxf' => 'image/vnd.dxf',
            'rvt', 'rfa', 'rte' => 'application/octet-stream',
            'ifc' => 'application/x-step',
            'skp' => 'application/octet-stream',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'tiff', 'tif' => 'image/tiff',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'txt' => 'text/plain',
            'xml' => 'application/xml',
            default => 'application/octet-stream',
        };
    }
}
