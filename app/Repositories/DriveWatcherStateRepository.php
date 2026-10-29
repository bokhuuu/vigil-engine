<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\Log;

class DriveWatcherStateRepository
{
    private string $path;

    private ?array $state = null;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? storage_path('app/google/drive-watcher-state.json');
    }

    // ----------------------------------------------------------------
    // Page token
    // ----------------------------------------------------------------

    public function getPageToken(): ?string
    {
        return $this->read()['page_token'] ?? null;
    }

    public function setPageToken(string $token): void
    {
        $state = $this->read();
        $state['page_token'] = $token;
        $this->write($state);
    }

    // ----------------------------------------------------------------
    // Folder index (Drive folder ID → ACT info)
    // ----------------------------------------------------------------
    public function getFolderIndex(): array
    {
        return $this->read()['folder_index'] ?? [];
    }

    /**
     * Replace the entire folder index (used during seeding).
     */
    public function setFolderIndex(array $index): void
    {
        $state = $this->read();
        $state['folder_index'] = $index;
        $this->write($state);
    }

    public function addActFolder(string $folderId, string $actNumber, string $actRecordId): void
    {
        $state = $this->read();
        $state['folder_index'][$folderId] = [
            'act_number' => $actNumber,
            'act_record_id' => $actRecordId,
        ];
        $this->write($state);
    }

    public function getActByFolderId(string $folderId): ?array
    {
        return $this->read()['folder_index'][$folderId] ?? null;
    }

    // ----------------------------------------------------------------
    // Subfolder index (Drive subfolder ID → parent ACT folder + name)
    // ----------------------------------------------------------------

    public function getSubfolderIndex(): array
    {
        return $this->read()['subfolder_index'] ?? [];
    }

    public function addSubfolder(string $subfolderId, string $actFolderId, string $name): void
    {
        $state = $this->read();

        foreach ($state['subfolder_index'] ?? [] as $existingId => $_) {
            if ($existingId === $subfolderId) {
                return;
            }
        }

        // Remove any stale entry for the same ACT folder + name (different ID)
        foreach ($state['subfolder_index'] ?? [] as $existingId => $existing) {
            if ($existing['act_folder_id'] === $actFolderId && $existing['name'] === $name) {
                error_log("DriveWatcherStateRepository: replacing stale subfolder [{$name}] ID [{$existingId}] with [{$subfolderId}]");
                unset($state['subfolder_index'][$existingId]);
                // Migrate state and color to new ID
                if (isset($state['subfolder_states'][$existingId])) {
                    $state['subfolder_states'][$subfolderId] = $state['subfolder_states'][$existingId];
                    unset($state['subfolder_states'][$existingId]);
                }
                if (isset($state['folder_colors'][$existingId])) {
                    $state['folder_colors'][$subfolderId] = $state['folder_colors'][$existingId];
                    unset($state['folder_colors'][$existingId]);
                }
                break;
            }
        }

        $state['subfolder_index'][$subfolderId] = [
            'act_folder_id' => $actFolderId,
            'name' => $name,
        ];
        $this->write($state);
    }

    public function getSubfolder(string $subfolderId): ?array
    {
        return $this->read()['subfolder_index'][$subfolderId] ?? null;
    }

    // ----------------------------------------------------------------
    // Subfolder states (full | empty | grace:<ms> | removed)
    // ----------------------------------------------------------------

    public function getSubfolderState(string $subfolderId): ?string
    {
        return $this->read()['subfolder_states'][$subfolderId] ?? null;
    }

    public function setSubfolderState(string $subfolderId, string $state): void
    {
        $data = $this->read();
        $data['subfolder_states'][$subfolderId] = $state;
        $this->write($data);
    }

    // ----------------------------------------------------------------
    // Folder colors (last known color per folder ID)
    // ----------------------------------------------------------------

    public function getFolderColor(string $folderId): ?string
    {
        return $this->read()['folder_colors'][$folderId] ?? null;
    }

    public function setFolderColor(string $folderId, string $color): void
    {
        $state = $this->read();
        $state['folder_colors'][$folderId] = $color;
        $this->write($state);
    }

    // ----------------------------------------------------------------
    // Internal
    // ----------------------------------------------------------------

    private function read(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        if (! file_exists($this->path)) {
            $this->state = [
                'page_token' => null,
                'folder_index' => [],
                'subfolder_index' => [],
                'subfolder_states' => [],
                'folder_colors' => [],
            ];

            return $this->state;
        }

        $contents = file_get_contents($this->path);
        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            Log::critical('DriveWatcherStateRepository: state file is corrupt or unreadable — starting with empty state', [
                'path' => $this->path,
                'json_error' => json_last_error_msg(),
            ]);
            $this->state = [
                'page_token' => null,
                'folder_index' => [],
                'subfolder_index' => [],
                'subfolder_states' => [],
                'folder_colors' => [],
            ];

            return $this->state;
        }

        $this->state = $decoded;

        return $this->state;
    }

    private function write(array $state): void
    {
        $this->state = $state;

        $dir = dirname($this->path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $this->path,
            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}
