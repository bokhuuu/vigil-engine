<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AirtableService;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class CreateDriveFoldersCommand extends Command
{
    protected $signature = 'vigil:create-drive-folders
        {--limit= : Maximum number of new ACTs to process}
        {--sync-minutes= : Override the sync window (default from config)}';

    protected $description = 'Create Drive folders for new ACTs, add missing and trash unneeded empty subfolders for recently changed ones';

    public function __construct(
        private AirtableService $airtable,
        private GoogleDriveService $drive,
        private TelegramService $telegram,
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
        if ($limit === 0) {
            return;
        }

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
     * ACTs with a folder that changed recently (flag ticked/unticked, business
     * or correspondence linked later): create missing subfolders, trash empty
     * ones that are no longer required, alert Zura about unneeded ones with files.
     */
    private function syncRecentlyChanged(): void
    {
        $minutes = $this->option('sync-minutes') !== null
            ? (int) $this->option('sync-minutes')
            : (int) config('vigil.drive.sync_window_minutes');

        $acts = $this->airtable->getRecentlyChangedActsWithFolder($minutes);

        $synced = 0;
        $failed = 0;

        foreach ($acts as $act) {
            $fields = $act['fields'] ?? [];
            $actNumber = (string) ($fields['🔖 აქტის ნომერი'] ?? $act['id']);

            try {
                $folderUrl = $fields['💼 საქაღალდე'];
                $this->drive->ensureActFolders($actNumber, $fields, $folderUrl);

                $keptWithFiles = $this->drive->pruneUnneededSubfolders($folderUrl, $fields);

                if ($keptWithFiles !== []) {
                    $this->telegram->sendToMe(
                        '⚠️ <b>ACT '.e($actNumber).'</b>: '.e(implode(', ', $keptWithFiles))
                            ." აღარ არის საჭირო, მაგრამ შეიცავს ფაილებს — შეამოწმე.\n"
                            .'🔗 <a href="'.e($folderUrl).'">საქაღალდე</a>'
                    );
                }

                $synced++;
            } catch (\Throwable $e) {
                $this->error("❌ sync {$actNumber} — {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Synced: {$synced}, Failed: {$failed} (changed in last {$minutes} min)");
    }
}
