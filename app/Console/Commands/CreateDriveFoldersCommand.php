<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AirtableService;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class CreateDriveFoldersCommand extends Command
{
    protected $signature = 'vigil:create-drive-folders {--limit= : Maximum number of ACTs to process}';

    protected $description = 'Create Drive folders for new ACTs and add missing subfolders for recently changed ones';

    public function __construct(
        private AirtableService $airtable,
        private GoogleDriveService $drive,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = $this->option('limit') !== null
            ? (int) $this->option('limit')
            : null;

        $this->createMissing($limit);
        $this->syncRecentlyChanged();

        return self::SUCCESS;
    }

    /**
     * New ACTs without a folder: create the folder tree and save its URL.
     */
    private function createMissing(?int $limit): void
    {
        $acts = $this->airtable->getActsWithoutDriveFolder($limit);

        if (empty($acts)) {
            $this->info('No ACTs without Drive folders found.');

            return;
        }

        $this->info('Processing '.count($acts).' ACTs...');

        $success = 0;
        $failed = 0;

        foreach ($acts as $act) {
            $recordId = $act['id'];
            $fields = $act['fields'] ?? [];
            $actNumber = (string) ($fields['🔖 აქტის ნომერი'] ?? $recordId);

            try {
                $folderUrl = $this->drive->ensureActFolders($actNumber, $fields);
                $this->airtable->updateActDriveFolder($recordId, $folderUrl);
                $this->info("✅ {$actNumber} — {$folderUrl}");
                $success++;
            } catch (\Throwable $e) {
                $this->error("❌ {$actNumber} — {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Done. Success: {$success}, Failed: {$failed}");
    }

    /**
     * ACTs with a folder that changed recently (flag ticked, business or
     * correspondence linked later): create only their missing subfolders.
     */
    private function syncRecentlyChanged(): void
    {
        $minutes = (int) config('vigil.drive.sync_window_minutes');
        $acts = $this->airtable->getRecentlyChangedActsWithFolder($minutes);

        $synced = 0;
        $failed = 0;

        foreach ($acts as $act) {
            $fields = $act['fields'] ?? [];
            $actNumber = (string) ($fields['🔖 აქტის ნომერი'] ?? $act['id']);

            try {
                $this->drive->ensureActFolders($actNumber, $fields, $fields['💼 საქაღალდე'] ?? null);
                $synced++;
            } catch (\Throwable $e) {
                $this->error("❌ sync {$actNumber} — {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Synced: {$synced}, Failed: {$failed} (changed in last {$minutes} min)");
    }
}
