<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DriveWatcherStateRepository;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;

class DriveChangesReader
{
    public function __construct(
        private GoogleDriveService $driveService,
        private DriveWatcherStateRepository $state,
    ) {}

    /**
     * Fetch changes from the Drive API since the last stored page token.
     * Returns the set of Drive file/folder IDs that changed.
     * Updates the page token in state after each successful call.
     *
     * @return array<string> Changed item IDs (files and folders mixed — caller filters)
     */
    public function getChangedIds(): array
    {
        $token = $this->state->getPageToken();

        if ($token === null) {
            Log::info('DriveChangesReader: no page token — run vigil:drive-watch-seed first');

            return [];
        }

        $drive = $this->driveService->getUserDrive();
        $changed = [];
        $newToken = $token;

        try {
            do {
                $response = $drive->changes->listChanges($newToken, [
                    'fields' => 'nextPageToken,newStartPageToken,changes(fileId,removed,file(id,name,mimeType,parents,trashed))',
                    'includeItemsFromAllDrives' => false,
                    'supportsAllDrives' => false,
                    'pageSize' => 1000,
                ]);

                foreach ($response->getChanges() as $change) {
                    $changed[] = $change->getFileId();

                    $file = $change->getFile();
                    if ($file !== null && $file->getParents()) {
                        foreach ($file->getParents() as $parentId) {
                            $changed[] = $parentId;
                        }
                    }
                }

                if ($response->getNextPageToken()) {
                    $newToken = $response->getNextPageToken();
                } else {
                    $newToken = $response->getNewStartPageToken() ?? $newToken;
                    break;
                }
            } while (true);

            $this->state->setPageToken($newToken);
        } catch (\Exception $e) {
            Log::error('DriveChangesReader: failed to fetch changes — '.$e->getMessage());

            return [];
        }

        Log::info('DriveChangesReader: '.count($changed).' changed IDs since last run');

        return array_unique($changed);
    }
}
