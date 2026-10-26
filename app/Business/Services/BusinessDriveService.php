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

        // Create fixed subfolders
        $subfolders = [
            'ფინანსური ანგარიშგება',
            'ხელფასები',
            'შემოსავლის ამონაწერი',
            'თანამშრომლები',
            'საბანკო ამონაწერი',
            'გამოთვლები',
            'სხვა',
        ];

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
        $roundId = $this->drive->findOrCreateFolder("მიმოწერა-{$round}", $mimotseraId);

        $this->drive->findOrCreateFolder('გამავალი', $roundId);
        $incomingId = $this->drive->findOrCreateFolder('შემომავალი', $roundId);

        $roundUrl = "https://drive.google.com/drive/folders/{$roundId}";

        // Update correspondence drive_folder_url
        Correspondence::on('business')
            ->where('business_id', $business->id)
            ->where('round', $round)
            ->update(['drive_folder_url' => $roundUrl]);

        Log::info("BusinessDriveService: created round [{$round}] folders for [{$business->name}]");

        return "https://drive.google.com/drive/folders/{$incomingId}";
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
}
