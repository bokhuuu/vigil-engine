<?php

declare(strict_types=1);

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    private ?Drive $drive = null;

    private ?Drive $userDrive = null;

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

    public function getUserDrive(): Drive
    {
        if ($this->userDrive === null) {
            $clientFile = config('services.google_drive.oauth_client');
            $tokenFile = config('services.google_drive.oauth_token');

            $client = new Client;
            $client->setAuthConfig($clientFile);
            $client->addScope(Drive::DRIVE);
            $client->setAccessType('offline');

            $token = json_decode(file_get_contents($tokenFile), true);
            $client->setAccessToken($token);

            if ($client->isAccessTokenExpired()) {
                $newToken = $client->fetchAccessTokenWithRefreshToken(
                    $client->getRefreshToken()
                );
                file_put_contents($tokenFile, json_encode($newToken, JSON_PRETTY_PRINT));
                $client->setAccessToken($newToken);
            }

            $this->userDrive = new Drive($client);
        }

        return $this->userDrive;
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
     * Tracked subfolders that are no longer required: empty ones are moved to
     * the trash (restorable), ones with files are left alone and returned so a
     * human can check them. Untracked folders (კორესპონდენცია, ბიზნესი) are
     * never touched.
     */
    public function pruneUnneededSubfolders(string $folderUrl, array $fields): array
    {
        $actFolderId = $this->folderIdFromUrl($folderUrl);

        if ($actFolderId === null) {
            throw new \InvalidArgumentException("Invalid Drive folder URL: {$folderUrl}");
        }

        $tracked = array_keys(config('vigil.drive.tracked_subfolders'));
        $required = $this->requirements->requiredSubfolders($fields);
        $keptWithFiles = [];

        foreach ($this->listChildFolders($actFolderId) as $name => $folderId) {
            if (! in_array($name, $tracked, true) || in_array($name, $required, true)) {
                continue;
            }

            if ($this->folderHasFiles($folderId)) {
                $keptWithFiles[] = $name;

                continue;
            }

            $this->trashFolder($folderId);
        }

        return $keptWithFiles;
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
    public function listChildFolders(string $parentId): array
    {
        $response = $this->drive()->files->listFiles([
            'q' => "'{$parentId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'fields' => 'files(id, name)',
            'pageSize' => 100,
        ]);

        $folders = [];

        foreach ($response->getFiles() as $file) {
            $folders[(string) $file->getName()] ??= $file->getId();
        }

        return $folders;
    }

    /**
     * List child folders using the OAuth user account (same account that creates them via ensureSubfolderPath).
     */
    public function listChildFoldersAsUser(string $parentId): array
    {
        $response = $this->getUserDrive()->files->listFiles([
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
     * Find a child folder by name using the OAuth user account.
     */
    public function findChildFolderAsUser(string $name, string $parentId): ?string
    {
        $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $name);

        $response = $this->getUserDrive()->files->listFiles([
            'q' => "name = '{$escaped}' and '{$parentId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'fields' => 'files(id)',
            'pageSize' => 1,
        ]);

        return ($response->getFiles()[0] ?? null)?->getId();
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
     * Whether a folder contains at least one child (file or subfolder).
     * Subfolders count because files may be nested inside them.
     */
    protected function folderHasFiles(string $folderId): bool
    {
        $response = $this->drive()->files->listFiles([
            'q' => "'{$folderId}' in parents and trashed = false",
            'fields' => 'files(id)',
            'pageSize' => 1,
        ]);

        return count($response->getFiles()) > 0;
    }

    /**
     * Move a folder to the Drive trash (restorable from Bin).
     */
    protected function trashFolder(string $folderId): void
    {
        $this->drive()->files->update($folderId, new DriveFile(['trashed' => true]));
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

    public function subfolderHasFiles(string $folderId): bool
    {
        $response = $this->getUserDrive()->files->listFiles([
            'q' => "'{$folderId}' in parents and trashed = false",
            'fields' => 'files(id)',
            'pageSize' => 1,
        ]);

        return count($response->getFiles()) > 0;
    }

    /**
     * Set folder color as the account owner (OAuth) so the color is visible.
     * Empty string removes the color.
     */
    public function setFolderColorAsUser(string $folderId, string $color): void
    {
        $metadata = new DriveFile;
        $metadata->setFolderColorRgb($color);

        $this->getUserDrive()->files->update($folderId, $metadata, [
            'fields' => 'id,folderColorRgb',
        ]);
    }

    /**
     * Get basic metadata for a Drive item: id, name, mimeType, parents.
     * Returns null if the item doesn't exist or is trashed.
     */
    public function getFileMetadata(string $fileId): ?array
    {
        try {
            $file = $this->getUserDrive()->files->get($fileId, [
                'fields' => 'id,name,mimeType,parents,trashed',
            ]);

            if ($file->getTrashed()) {
                return null;
            }

            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'parents' => $file->getParents() ?? [],
            ];
        } catch (\Exception $e) {
            Log::warning("GoogleDriveService: could not get metadata for [{$fileId}] — ".$e->getMessage());

            return null;
        }
    }

    /**
     * List tracked subfolders of an ACT folder.
     * Returns subfolderId => name for subfolders matching the tracked list.
     */
    public function listTrackedSubfolders(string $actFolderId, array $tracked): array
    {
        $all = $this->listChildFolders($actFolderId);
        $result = [];

        foreach ($all as $name => $id) {
            if (in_array($name, $tracked, true)) {
                $result[$id] = $name;
            }
        }

        return $result;
    }

    // app/Services/GoogleDriveService.php
    // Add before the final closing }

    /**
     * Find a child folder by name, creating it if it doesn't exist.
     */
    public function findOrCreateFolder(string $name, string $parentId): string
    {
        return $this->findChildFolder($name, $parentId) ?? $this->createFolder($name, $parentId);
    }

    /**
     * Upload a file to a Drive folder and return its file ID.
     */
    public function uploadFile(string $parentFolderId, string $filename, string $content, string $mimeType): string
    {
        $metadata = new DriveFile([
            'name' => $filename,
            'parents' => [$parentFolderId],
        ]);

        $file = $this->getUserDrive()->files->create($metadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id',
        ]);

        return $file->getId();
    }

    public function ensureSubfolderPath(string $folderUrl, string $subfolderPath): string
    {
        $actFolderId = $this->folderIdFromUrl($folderUrl);

        if ($actFolderId === null) {
            throw new \InvalidArgumentException("Invalid Drive folder URL: {$folderUrl}");
        }

        $parentId = $actFolderId;
        foreach (explode('/', $subfolderPath) as $segment) {
            $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $segment);
            $response = $this->getUserDrive()->files->listFiles([
                'q' => "name = '{$escaped}' and '{$parentId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
                'fields' => 'files(id)',
                'pageSize' => 1,
            ]);

            $existing = ($response->getFiles()[0] ?? null)?->getId();

            if ($existing) {
                $parentId = $existing;
            } else {
                $folder = $this->getUserDrive()->files->create(new DriveFile([
                    'name' => $segment,
                    'mimeType' => 'application/vnd.google-apps.folder',
                    'parents' => [$parentId],
                ]), ['fields' => 'id']);
                $parentId = $folder->getId();
            }
        }

        return $parentId;
    }

    public function uploadFileToSubfolder(string $folderUrl, string $subfolderPath, string $filename, string $mimeType, string $data): string
    {
        $folderId = $this->ensureSubfolderPath($folderUrl, $subfolderPath);

        $metadata = new DriveFile([
            'name' => $filename,
            'parents' => [$folderId],
        ]);

        $file = $this->getUserDrive()->files->create($metadata, [
            'data' => $data,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id',
        ]);

        return $file->getId();
    }

    /**
     * Find or create a named subfolder inside an ACT folder URL.
     * Returns the subfolder ID.
     */
    public function findOrCreateSubfolder(string $folderUrl, string $subfolderName): string
    {
        $actFolderId = $this->folderIdFromUrl($folderUrl);

        if ($actFolderId === null) {
            throw new \InvalidArgumentException("Invalid Drive folder URL: {$folderUrl}");
        }

        return $this->findChildFolder($subfolderName, $actFolderId)
            ?? $this->createFolder($subfolderName, $actFolderId);
    }

    /**
     * List direct subfolders of a Drive folder URL.
     * Returns an array of folder names, excluding any in $exclude.
     */
    public function listSubfoldersFromUrl(string $folderUrl, array $exclude = []): array
    {
        $folderId = $this->folderIdFromUrl($folderUrl);

        if ($folderId === null) {
            throw new \InvalidArgumentException("Invalid Drive folder URL: {$folderUrl}");
        }

        $folders = $this->listChildFolders($folderId);

        return array_values(array_filter(
            array_keys($folders),
            fn (string $name) => ! in_array($name, $exclude, true),
        ));
    }

    /**
     * Rename a Drive folder by its ID.
     */
    public function renameFolder(string $folderId, string $newName): void
    {
        $this->getUserDrive()->files->update(
            $folderId,
            new DriveFile(['name' => $newName]),
            ['fields' => 'id,name'],
        );
    }

    /**
     * Extract folder ID from a Drive URL — public wrapper.
     */
    public function getFolderIdFromUrl(?string $url): ?string
    {
        return $this->folderIdFromUrl($url);
    }

    /**
     * List non-folder files directly inside a Drive folder.
     * Returns [['id' => ..., 'name' => ..., 'mimeType' => ...], ...]
     */
    public function listFilesInFolder(string $folderId): array
    {
        $response = $this->getUserDrive()->files->listFiles([
            'q' => "'{$folderId}' in parents and mimeType != 'application/vnd.google-apps.folder' and trashed = false",
            'fields' => 'files(id, name, mimeType)',
            'pageSize' => 100,
        ]);

        $files = [];
        foreach ($response->getFiles() as $file) {
            $files[] = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
            ];
        }

        return $files;
    }

    /**
     * Move a file to a new parent folder (Drive-side, no re-upload).
     */
    public function moveFile(string $fileId, string $newParentId): void
    {
        $meta = $this->getUserDrive()->files->get($fileId, ['fields' => 'parents']);
        $oldParents = implode(',', $meta->getParents() ?? []);

        $this->getUserDrive()->files->update(
            $fileId,
            new DriveFile,
            [
                'addParents' => $newParentId,
                'removeParents' => $oldParents,
                'fields' => 'id,parents',
            ],
        );
    }

    /**
     * Download a file by its Drive ID and return the raw binary content.
     * Uses OAuth user drive so permissions match the folder owner account.
     */
    public function downloadFile(string $fileId): string
    {
        $response = $this->getUserDrive()->files->get($fileId, ['alt' => 'media']);

        return $response->getBody()->getContents();
    }
}
