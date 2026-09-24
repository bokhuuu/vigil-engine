<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AirtableService;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class CreateDriveFoldersCommand extends Command
{
    protected $signature = 'vigil:create-drive-folders {--limit= : Maximum number of ACTs to process}';

    protected $description = 'Create Google Drive folders for ACTs that do not have one yet';

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

        $acts = $this->airtable->getActsWithoutDriveFolder($limit);

        if (empty($acts)) {
            $this->info('No ACTs without Drive folders found.');

            return self::SUCCESS;
        }

        $this->info('Processing '.count($acts).' ACTs...');

        $success = 0;
        $failed = 0;

        foreach ($acts as $act) {
            $recordId = $act['id'];
            $fields = $act['fields'] ?? [];
            $actNumber = $fields['🔖 აქტის ნომერი'] ?? $recordId;

            try {
                $folderUrl = $this->drive->ensureActFolders((string) $actNumber, $fields);
                $this->airtable->updateActDriveFolder($recordId, $folderUrl);
                $this->info("✅ {$actNumber} — {$folderUrl}");
                $success++;
            } catch (\Throwable $e) {
                $this->error("❌ {$actNumber} — {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Done. Success: {$success}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
