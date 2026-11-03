<?php

declare(strict_types=1);

namespace App\Business\Services;

use App\Business\Models\Business;
use App\Business\Models\Correspondence;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Log;

class BusinessDriveService
{
    private string $rootFolderId;

    public function __construct(private readonly GoogleDriveService $drive)
    {
        $this->rootFolderId = config('services.google_drive.business_folder_id');
    }

    public function createBusinessFolder(Business $business): string
    {
        $folderName = "{$business->name} ({$business->registration_code})";

        $folderId = $this->drive->findOrCreateFolder($folderName, $this->rootFolderId);
        $folderUrl = "https://drive.google.com/drive/folders/{$folderId}";

        $subfolders = config('vigil.business.drive_subfolders');

        foreach ($subfolders as $subfolder) {
            $this->drive->findOrCreateFolder($subfolder, $folderId);
        }

        // Create მიმოწერა/ root folder
        $this->drive->findOrCreateFolder('მიმოწერა', $folderId);

        $business->update(['drive_folder_url' => $folderUrl]);

        Log::info("BusinessDriveService: created folder for [{$business->name}] → {$folderUrl}");

        return $folderUrl;
    }

    public function createCorrespondenceRoundFolders(Business $business, int $round): string
    {
        if (! $business->drive_folder_url) {
            $this->createBusinessFolder($business);
            $business->refresh();
        }

        $folderId = $this->getFolderIdFromUrl($business->drive_folder_url);
        $mimotseraId = $this->drive->findOrCreateFolder('მიმოწერა', $folderId);
        $roundId = $this->drive->findOrCreateFolder((string) $round, $mimotseraId);

        $roundUrl = "https://drive.google.com/drive/folders/{$roundId}";

        // Update correspondence drive_folder_url
        Correspondence::on('business')
            ->where('business_id', $business->id)
            ->where('round', $round)
            ->update(['drive_folder_url' => $roundUrl]);

        Log::info("BusinessDriveService: created round [{$round}] folders for [{$business->name}]");

        return $roundUrl;
    }

    public function uploadFileToDrive(string $parentFolderId, string $filename, string $content, string $mimeType): string
    {
        return $this->drive->uploadFile($parentFolderId, $filename, $content, $mimeType);
    }

    public function getFolderIdFromUrl(string $url): string
    {
        preg_match('/\/folders\/([a-zA-Z0-9_-]+)/', $url, $matches);

        return $matches[1] ?? '';
    }

    public function renameBusinessFolder(string $folderId, string $newName): void
    {
        $this->drive->renameFolder($folderId, $newName);
    }

    /**
     * List files currently in მიმოწერა/ for incoming rounds only (received, not sent by us).
     * Returns [['id' => ..., 'name' => ..., 'mimeType' => ..., 'round' => ...], ...]
     */
    public function listIncomingFiles(Business $business): array
    {
        if (! $business->drive_folder_url) {
            return [];
        }

        $incomingRounds = $business->correspondence()
            ->whereNotNull('drive_folder_url')
            ->pluck('round')
            ->map(fn ($r) => (string) $r)
            ->toArray();

        if (empty($incomingRounds)) {
            return [];
        }

        $businessFolderId = $this->getFolderIdFromUrl($business->drive_folder_url);
        $mimotsera = $this->drive->findChildFolderAsUser('მიმოწერა', $businessFolderId);
        $files = [];

        if ($mimotsera === null) {
            return [];
        }

        foreach ($this->drive->listChildFoldersAsUser($mimotsera) as $roundName => $roundId) {
            if (! in_array((string) $roundName, $incomingRounds, true)) {
                continue;
            }
            foreach ($this->drive->listFilesInFolder($roundId) as $file) {
                $files[] = array_merge($file, ['round' => $roundName]);
            }
        }

        return $files;
    }

    /**
     * Move a file to a named top-level business subfolder.
     */
    public function moveFileToSubfolder(Business $business, string $fileId, string $subfolderName): void
    {
        $businessFolderId = $this->getFolderIdFromUrl($business->drive_folder_url);
        $destinationId = $this->drive->findOrCreateFolder($subfolderName, $businessFolderId);

        $this->drive->moveFile($fileId, $destinationId);
    }
}
