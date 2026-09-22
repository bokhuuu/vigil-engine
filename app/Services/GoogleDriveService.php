<?php

declare(strict_types=1);

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class GoogleDriveService
{
    private ?Drive $drive = null;

    /*
    |--------------------------------------------------------------------------
    | Subfolder rules
    |--------------------------------------------------------------------------
    |
    | ფოტოები        — always
    | სკანი           — always
    | შიდა ნახაზები  — only if 🏠 შენობა/ნაგებობები
    | გამიჯვნები     — only if 📋 გამიჯვნა საჭიროა
    | ხარჯთაღრიცხვა — if any of: 🏠 შენობა/ნაგებობები, 🏠 ღობე - ჭიშკარი,
    |                             🏠 გადასატანი აქტივები, 🏠 ეზოს-მოპირკეთება,
    |                             🏠 სამ-ფაზიანი დენი
    | ტოპო ნარგავები — if 🏠 მრავალწლოვანი or 🏠 ერთწლოვანი
    |
    */

    public function __construct() {}

    private function drive(): Drive
    {
        if ($this->drive === null) {
            $client = new Client;
            $client->setAuthConfig(config('services.google_drive.credentials'));
            $client->addScope(Drive::DRIVE);
            $this->drive = new Drive($client);
        }

        return $this->drive;
    }

    /**
     * Create the ACT root folder and all required subfolders based on property flags.
     * Returns the URL of the ACT root folder.
     */
    public function createActFolders(string $actNumber, array $fields): string
    {
        $rootFolderId = config('services.google_drive.act_folder_id');

        $actFolderId = $this->createFolder($actNumber, $rootFolderId);

        // Always created
        $this->createFolder('ფოტოები', $actFolderId);
        $this->createFolder('სკანი', $actFolderId);

        // შიდა ნახაზები — only if building exists
        if ($fields['🏠 შენობა/ნაგებობები'] ?? false) {
            $this->createFolder('შიდა ნახაზები', $actFolderId);
        }

        // გამიჯვნები — only if demarcation required
        if ($fields['📋 გამიჯვნა საჭიროა'] ?? false) {
            $this->createFolder('გამიჯვნები', $actFolderId);
        }

        // ხარჯთაღრიცხვა — if any cost-triggering flag is set
        $needsCost = ($fields['🏠 შენობა/ნაგებობები'] ?? false)
            || ($fields['🏠 ღობე - ჭიშკარი'] ?? false)
            || ($fields['🏠 გადასატანი აქტივები'] ?? false)
            || ($fields['🏠 ეზოს-მოპირკეთება'] ?? false)
            || ($fields['🏠 სამ-ფაზიანი დენი'] ?? false);

        if ($needsCost) {
            $this->createFolder('ხარჯთაღრიცხვა', $actFolderId);
        }

        // ტოპო ნარგავები — if any plants
        if (($fields['🏠 მრავალწლოვანი'] ?? false) || ($fields['🏠 ერთწლოვანი'] ?? false)) {
            $this->createFolder('ტოპო ნარგავები', $actFolderId);
        }

        // ბიზნესი — one subfolder per linked business
        $businesses = $fields['🏢 ბიზნესი'] ?? [];
        if (! empty($businesses)) {
            $businessFolderId = $this->createFolder('ბიზნესი', $actFolderId);
            foreach (array_values($businesses) as $index => $businessId) {
                $this->createFolder('ბიზნესი-'.($index + 1), $businessFolderId);
            }
        }

        // კორესპონდენცია — if any correspondence linked
        $correspondence = $fields['📨 კორესპონდენცია'] ?? [];
        if (! empty($correspondence)) {
            $this->createFolder('კორესპონდენცია', $actFolderId);
        }

        return "https://drive.google.com/drive/folders/{$actFolderId}";
    }

    /**
     * Create a single folder inside a parent and return its ID.
     */
    private function createFolder(string $name, string $parentId): string
    {
        $metadata = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);

        $folder = $this->drive()->files->create($metadata, [
            'fields' => 'id',
        ]);

        return $folder->getId();
    }
}
