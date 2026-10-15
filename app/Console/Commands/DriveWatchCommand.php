<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\DriveWatcherStateRepository;
use App\Services\DriveChangesReader;
use App\Services\DriveWatcherService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DriveWatchCommand extends Command
{
    protected $signature = 'vigil:drive-watch
                            {--dry-run : Log what would happen without changing anything}
                            {--seed : Seed the folder index from Airtable and initialise the page token, then exit}';

    protected $description = 'Engine-based Drive watcher — detects file changes and ticks Airtable checkboxes.';

    public function __construct(
        private DriveWatcherService $watcher,
        private DriveChangesReader $reader,
        private DriveWatcherStateRepository $state,
        private TelegramService $telegram,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($this->option('seed')) {
            return $this->runSeed();
        }

        if ($this->state->getPageToken() === null) {
            $this->error('No page token found. Run: php artisan vigil:drive-watch --seed');

            return self::FAILURE;
        }

        if ($dryRun) {
            Log::info('vigil:drive-watch [DRY RUN] started');
        }

        try {
            $changedIds = $this->reader->getChangedIds();
            $this->watcher->process($changedIds, $dryRun);
        } catch (\Exception $e) {
            Log::error('vigil:drive-watch failed: '.$e->getMessage());

            $this->telegram->sendToMe(
                '⚠️ <b>vigil:drive-watch</b> შეცდომა:'."\n".e($e->getMessage())
            );

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function runSeed(): int
    {
        $this->info('Seeding folder index from Airtable...');
        $count = $this->watcher->seedFromAirtable();
        $this->info("Seeded {$count} ACT folders.");

        if ($this->state->getPageToken() === null) {
            $this->info('Initialising Drive page token...');
            $this->watcher->initPageToken();
        } else {
            $this->info('Page token already exists — skipping init.');
        }

        $this->info('Done. The watcher is ready to run.');

        return self::SUCCESS;
    }
}
