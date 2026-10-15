<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DriveWatcherStateRepository;
use Illuminate\Support\Facades\Log;

class DriveWatcherService
{
    private const GRACE_MS = 300_000; // 5 minutes in milliseconds

    private const COLOR_GREEN = '#00843D';

    private const COLOR_YELLOW = '#F6C026';

    private const COLOR_NONE = '';

    public function __construct(
        private AirtableService $airtable,
        private GoogleDriveService $driveService,
        private DriveWatcherStateRepository $state,
        private ManagerNotifier $notifier,
        private DriveEventService $events,
    ) {}

    /**
     * Process a set of changed Drive item IDs.
     * Each ID is looked up in the subfolder index; unknown IDs are skipped.
     * Dry-run mode logs what would happen without touching Airtable or colors.
     *
     * @param  array<string>  $changedIds
     */
    public function process(array $changedIds, bool $dryRun = false): void
    {
        if (empty($changedIds)) {
            return;
        }

        $nowMs = (int) (microtime(true) * 1000);

        // Collect which ACT folders need a color update after processing
        $actFoldersDirty = [];

        foreach ($changedIds as $id) {
            $subfolder = $this->state->getSubfolder($id);

            if ($subfolder === null) {
                $this->maybeIndexNewSubfolder($id);
                // For files, the ID itself won't be in subfolder_index —
                // we indexed the parent subfolder, so re-look using parent
                $meta = $this->driveService->getFileMetadata($id);
                if ($meta !== null) {
                    foreach ($meta['parents'] as $parentId) {
                        $subfolder = $this->state->getSubfolder($parentId);
                        if ($subfolder !== null) {
                            $id = $parentId;
                            break;
                        }
                    }
                }
                $subfolder = $this->state->getSubfolder($id);
                if ($subfolder === null) {
                    continue;
                }
            }

            $actFolderId = $subfolder['act_folder_id'];
            $name = $subfolder['name'];

            $actInfo = $this->state->getActByFolderId($actFolderId);

            if ($actInfo === null) {
                Log::warning("DriveWatcherService: subfolder [{$name}] has no ACT entry");

                continue;
            }

            $actNumber = $actInfo['act_number'];
            $hasFiles = $this->driveService->subfolderHasFiles($id);
            $prevState = $this->state->getSubfolderState($id);

            $newState = $this->reconcileSubfolder(
                $actNumber,
                $name,
                $id,
                $hasFiles,
                $prevState,
                $nowMs,
                $dryRun
            );

            if ($newState !== $prevState) {
                if (! $dryRun) {
                    $this->state->setSubfolderState($id, $newState);
                }
                $actFoldersDirty[$actFolderId] = true;
            }

            // Update subfolder color
            $this->updateSubfolderColor($id, $hasFiles, $dryRun);
        }

        // Check grace timers even when no new changes arrived for them
        $this->processGraceTimers($nowMs, $dryRun, $actFoldersDirty);

        // Update root ACT folder colors for all affected ACTs
        foreach (array_keys($actFoldersDirty) as $actFolderId) {
            $this->updateRootFolderColor($actFolderId, $dryRun);
        }
    }

    /**
     * Check all subfolder states that are in grace period,
     * even if they didn't appear in the current change set.
     *
     * @param  array<string, bool>  $dirty  Accumulated dirty ACT folder IDs
     */
    public function processGraceTimers(int $nowMs, bool $dryRun, array &$dirty = []): void
    {
        foreach ($this->state->getSubfolderIndex() as $subfolderId => $subfolder) {
            $stateVal = $this->state->getSubfolderState($subfolderId);

            if ($stateVal === null || ! str_starts_with($stateVal, 'grace:')) {
                continue;
            }

            $since = (int) explode(':', $stateVal)[1];

            if ($nowMs - $since < self::GRACE_MS) {
                continue;
            }

            // Grace period expired — confirm folder is still empty
            $hasFiles = $this->driveService->subfolderHasFiles($subfolderId);

            if ($hasFiles) {
                if (! $dryRun) {
                    $this->state->setSubfolderState($subfolderId, 'full');
                }
                Log::info("DriveWatcherService: grace cancelled for [{$subfolder['name']}] — files returned");
                $dirty[$subfolder['act_folder_id']] = true;

                continue;
            }

            $actInfo = $this->state->getActByFolderId($subfolder['act_folder_id']);

            if ($actInfo === null) {
                continue;
            }

            Log::info("DriveWatcherService: grace expired for [{$subfolder['name']}] on ACT [{$actInfo['act_number']}]".($dryRun ? ' [DRY RUN]' : ''));

            if (! $dryRun) {
                // $this->events->fileRemoved($actInfo['act_number'], $subfolder['name']);
                $this->state->setSubfolderState($subfolderId, 'removed');
            }

            $dirty[$subfolder['act_folder_id']] = true;
        }
    }

    /**
     * Seed the folder index from Airtable.
     * Reads all ACTs that have a Drive folder URL, builds the ACT folder index,
     * then walks each ACT folder's subfolders to build the subfolder index.
     */
    public function seedFromAirtable(): int
    {
        $acts = $this->airtable->getAllActs();
        $index = [];
        $count = 0;

        foreach ($acts as $act) {
            $folderUrl = $act['fields']['💼 საქაღალდე'] ?? null;
            $actNumber = (string) ($act['fields']['🔖 აქტის ნომერი'] ?? '');

            if (! $folderUrl || $actNumber === '') {
                continue;
            }

            if (! preg_match('#folders/([A-Za-z0-9_-]+)#', $folderUrl, $m)) {
                continue;
            }

            $index[$m[1]] = [
                'act_number' => AirtableService::normalizeActNumber($actNumber),
                'act_record_id' => $act['id'],
            ];
            $count++;
        }

        $this->state->setFolderIndex($index);
        Log::info("DriveWatcherService: seeded {$count} ACT folders from Airtable");

        // Walk subfolders for each ACT folder and build subfolder index
        $tracked = array_keys(config('vigil.drive.tracked_subfolders'));
        $subfolderCount = 0;

        foreach ($index as $actFolderId => $actInfo) {
            try {
                $subfolders = $this->driveService->listTrackedSubfolders($actFolderId, $tracked);

                foreach ($subfolders as $subfolderId => $name) {
                    $this->state->addSubfolder($subfolderId, $actFolderId, $name);
                    $subfolderCount++;
                }
            } catch (\Exception $e) {
                Log::warning("DriveWatcherService: could not walk subfolders for ACT [{$actInfo['act_number']}] — ".$e->getMessage());
            }
        }

        Log::info("DriveWatcherService: seeded {$subfolderCount} subfolders");

        return $count;
    }

    /**
     * Initialise the Drive changes page token to "now",
     * so the watcher only sees changes going forward.
     */
    public function initPageToken(): void
    {
        $drive = $this->driveService->getUserDrive();
        $response = $drive->changes->getStartPageToken();
        $token = $response->getStartPageToken();
        $this->state->setPageToken($token);
        Log::info("DriveWatcherService: page token initialised to [{$token}]");
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------

    private function reconcileSubfolder(
        string $actNumber,
        string $name,
        string $subfolderId,
        bool $hasFiles,
        ?string $prev,
        int $nowMs,
        bool $dryRun,
    ): string {
        if ($hasFiles) {
            if ($prev === 'full') {
                return 'full'; // no change
            }

            Log::info("DriveWatcherService: files added in [{$name}] on ACT [{$actNumber}]".($dryRun ? ' [DRY RUN]' : ''));

            if (! $dryRun) {
                $this->events->fileAdded($actNumber, $name);
            }

            return 'full';
        }

        // Folder is empty
        if ($prev === 'full') {
            Log::info("DriveWatcherService: [{$name}] went empty on ACT [{$actNumber}] — grace started".($dryRun ? ' [DRY RUN]' : ''));

            return 'grace:'.$nowMs;
        }

        if ($prev !== null && str_starts_with($prev, 'grace:')) {
            return $prev;
        }

        if ($prev === 'removed') {
            return 'removed';
        }

        return 'empty';
    }

    private function sendRemovalAlert(string $actNumber, string $subfolderName, string $actFolderId): void
    {
        $this->events->fileRemoved($actNumber, $subfolderName);
    }

    private function updateSubfolderColor(string $subfolderId, bool $hasFiles, bool $dryRun): void
    {
        $meta = $this->driveService->getFileMetadata($subfolderId);

        if ($meta === null || $meta['mimeType'] !== 'application/vnd.google-apps.folder') {
            return;
        }

        $color = $hasFiles ? self::COLOR_GREEN : self::COLOR_NONE;
        $prev = $this->state->getFolderColor($subfolderId);

        if ($prev === $color) {
            return;
        }

        Log::info("DriveWatcherService: subfolder color → [{$color}]".($dryRun ? ' [DRY RUN]' : ''));

        if (! $dryRun) {
            $this->driveService->setFolderColorAsUser($subfolderId, $color);
            $this->state->setFolderColor($subfolderId, $color);
        }
    }

    private function updateRootFolderColor(string $actFolderId, bool $dryRun): void
    {
        $actInfo = $this->state->getActByFolderId($actFolderId);

        if ($actInfo === null) {
            return;
        }

        $actNumber = $actInfo['act_number'];
        $subfolders = $this->state->getSubfolderIndex();
        $tracked = array_keys(config('vigil.drive.tracked_subfolders'));

        $totalTracked = 0;
        $fullCount = 0;

        foreach ($subfolders as $subId => $sub) {
            if ($sub['act_folder_id'] !== $actFolderId) {
                continue;
            }
            if (! in_array($sub['name'], $tracked, true)) {
                continue;
            }
            $totalTracked++;
            $state = $this->state->getSubfolderState($subId);
            if ($state === 'full') {
                $fullCount++;
            }
        }

        // Also check signatures from Airtable
        $act = $this->airtable->getActByNumber($actNumber);
        $hasSig = ! empty($act['fields']['📥 ხელმოწერები'] ?? false);

        if ($totalTracked === 0) {
            $color = self::COLOR_NONE;
        } elseif ($fullCount === $totalTracked && $hasSig) {
            $color = self::COLOR_GREEN;
        } elseif ($fullCount > 0) {
            $color = self::COLOR_YELLOW;
        } else {
            $color = self::COLOR_NONE;
        }

        $prev = $this->state->getFolderColor($actFolderId);

        if ($prev === $color) {
            return;
        }

        Log::info("DriveWatcherService: root color for ACT [{$actNumber}] → [{$color}]".($dryRun ? ' [DRY RUN]' : ''));

        if (! $dryRun) {
            $this->driveService->setFolderColorAsUser($actFolderId, $color);
            $this->state->setFolderColor($actFolderId, $color);
        }
    }

    private function maybeIndexNewSubfolder(string $id): void
    {
        $tracked = array_keys(config('vigil.drive.tracked_subfolders'));
        $meta = $this->driveService->getFileMetadata($id);

        if ($meta === null) {
            return;
        }

        $isFolder = $meta['mimeType'] === 'application/vnd.google-apps.folder';

        if ($isFolder) {
            if (! in_array($meta['name'], $tracked, true)) {
                return;
            }

            foreach ($meta['parents'] as $parentId) {
                if ($this->state->getActByFolderId($parentId) !== null) {
                    $this->state->addSubfolder($id, $parentId, $meta['name']);
                    Log::info("DriveWatcherService: auto-indexed new subfolder [{$meta['name']}] under ACT folder [{$parentId}]");

                    return;
                }
            }
        } else {
            $parentId = $meta['parents'][0] ?? null;

            while ($parentId !== null) {
                $subfolder = $this->state->getSubfolder($parentId);

                if ($subfolder !== null) {
                    $this->state->addSubfolder($id, $subfolder['act_folder_id'], $subfolder['name']);
                    Log::info("DriveWatcherService: mapped file to subfolder [{$subfolder['name']}] via parent chain");

                    return;
                }

                $parentMeta = $this->driveService->getFileMetadata($parentId);

                if ($parentMeta === null) {
                    break;
                }

                if (in_array($parentMeta['name'], $tracked, true)) {
                    foreach ($parentMeta['parents'] as $grandParentId) {
                        if ($this->state->getActByFolderId($grandParentId) !== null) {
                            $this->state->addSubfolder($parentId, $grandParentId, $parentMeta['name']);
                            $this->state->addSubfolder($id, $grandParentId, $parentMeta['name']);
                            Log::info("DriveWatcherService: auto-indexed subfolder [{$parentMeta['name']}] via parent chain");

                            return;
                        }
                    }
                }

                if ($this->state->getActByFolderId($parentId) !== null) {
                    break;
                }

                $parentId = $parentMeta['parents'][0] ?? null;
            }
        }
    }
}
