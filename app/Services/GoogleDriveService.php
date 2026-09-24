<?php

declare(strict_types=1);

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class GoogleDriveService
{
    private ?Drive $drive = null;

    public function __construct(
        private ActRequirements $requirements,
    ) {}

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
     * Make sure the ACT folder and every required subfolder exist.
     * Reuses what is already there and creates only what is missing,
     * so it is safe to run any number of times. Returns the ACT folder URL.
     */
    public function ensureActFolders(string $actNumber, array $fields, ?string $folderUrl = null): string
    {
        $rootFolderId = config('services.google_drive.act_folder_id');

        $actFolderId = $this->folderIdFromUrl($folderUrl)
            ?? $this->findChildFolder($actNumber, $rootFolderId)
            ?? $this->createFolder($actNumber, $rootFolderId);

        $existing = $this->listChildFolders($actFolderId);

        foreach ($this->requirements->requiredSubfolders($fields) as $name) {
            $existing[$name] ??= $this->createFolder($name, $actFolderId);
        }

        // ბიზნესი/ბიზნესი-1, -2, … — one per linked business
        $businessCount = count($fields['🏢 ბიზნესი'] ?? []);

        if ($businessCount > 0) {
            $businessFolderId = $existing['ბიზნესი'] ??= $this->createFolder('ბიზნესი', $actFolderId);
            $existingBusinesses = $this->listChildFolders($businessFolderId);

            for ($i = 1; $i <= $businessCount; $i++) {
                $name = "ბიზნესი-{$i}";

                if (! isset($existingBusinesses[$name])) {
                    $this->createFolder($name, $businessFolderId);
                }
            }
        }

        // კორესპონდენცია — if any correspondence is linked
        if (! empty($fields['📨 კორესპონდენცია'] ?? []) && ! isset($existing['კორესპონდენცია'])) {
            $this->createFolder('კორესპონდენცია', $actFolderId);
        }

        return "https://drive.google.com/drive/folders/{$actFolderId}";
    }

    /**
     * Required subfolders of an ACT that are missing or have no files.
     * An empty result means everything required has been uploaded.
     *
     * @return array<int, string>
     */
    public function emptyRequiredSubfolders(string $folderUrl, array $fields): array
    {
        $actFolderId = $this->folderIdFromUrl($folderUrl);

        if ($actFolderId === null) {
            throw new \InvalidArgumentException("Invalid Drive folder URL: {$folderUrl}");
        }

        $existing = $this->listChildFolders($actFolderId);
        $empty = [];

        foreach ($this->requirements->requiredSubfolders($fields) as $name) {
            if (! isset($existing[$name]) || ! $this->folderHasFiles($existing[$name])) {
                $empty[] = $name;
            }
        }

        return $empty;
    }

    /**
     * Folder ID from a Drive folder URL, or null.
     */
    private function folderIdFromUrl(?string $url): ?string
    {
        if ($url !== null && preg_match('#folders/([A-Za-z0-9_-]+)#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Child folders of a parent, as name => id.
     */
    protected function listChildFolders(string $parentId): array
    {
        $response = $this->drive()->files->listFiles([
            'q' => "'{$parentId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'fields' => 'files(id, name)',
            'pageSize' => 100,
        ]);

        $folders = [];

        foreach ($response->getFiles() as $file) {
            $folders[$file->getName()] ??= $file->getId();
        }

        return $folders;
    }

    /**
     * Find a child folder by exact name, or null.
     */
    protected function findChildFolder(string $name, string $parentId): ?string
    {
        $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $name);

        $response = $this->drive()->files->listFiles([
            'q' => "name = '{$escaped}' and '{$parentId}' in parents"
                ." and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'fields' => 'files(id)',
            'pageSize' => 1,
        ]);

        return ($response->getFiles()[0] ?? null)?->getId();
    }

    /**
     * Create a single folder inside a parent and return its ID.
     */
    protected function createFolder(string $name, string $parentId): string
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

    /**
     * Whether a folder contains at least one file (subfolders don't count).
     */
    protected function folderHasFiles(string $folderId): bool
    {
        $response = $this->drive()->files->listFiles([
            'q' => "'{$folderId}' in parents and trashed = false"
                ." and mimeType != 'application/vnd.google-apps.folder'",
            'fields' => 'files(id)',
            'pageSize' => 1,
        ]);

        return count($response->getFiles()) > 0;
    }

    /**
     * Set the color of a Drive folder.
     * Green = '#00843D', Yellow = '#F6C026', null = remove color.
     */
    public function setFolderColor(string $folderId, ?string $color): void
    {
        $metadata = new DriveFile;

        if ($color !== null) {
            $metadata->setFolderColorRgb($color);
        } else {
            $metadata->setFolderColorRgb('');
        }

        $this->drive()->files->update($folderId, $metadata, [
            'fields' => 'id,folderColorRgb',
        ]);
    }
}
