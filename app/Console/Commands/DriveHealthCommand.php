<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\DriveWatcherStateRepository;
use App\Services\DriveWatcherService;
use Illuminate\Console\Command;

class DriveHealthCommand extends Command
{
    protected $signature = 'vigil:drive-health {--fix : Automatically reseed if issues are found}';

    protected $description = 'Cross-reference drive-watcher-state.json sections and report mismatches.';

    public function __construct(
        private DriveWatcherStateRepository $state,
        private DriveWatcherService $watcher,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $folderIndex = $this->state->getFolderIndex();
        $subfolderIndex = $this->state->getSubfolderIndex();
        $issues = 0;

        $seen = [];

        foreach ($subfolderIndex as $subfolderId => $sub) {
            $actFolderId = $sub['act_folder_id'];
            $name = $sub['name'];

            // 1. Orphaned subfolder — parent ACT folder not in folder_index
            if (! isset($folderIndex[$actFolderId])) {
                $this->warn("ORPHAN: subfolder [{$name}] ID [{$subfolderId}] has no ACT folder entry for [{$actFolderId}]");
                $issues++;
            }

            // 2. Missing subfolder_states entry
            if ($this->state->getSubfolderState($subfolderId) === null) {
                $actNumber = $folderIndex[$actFolderId]['act_number'] ?? $actFolderId;
                $this->warn("MISSING STATE: subfolder [{$name}] on ACT [{$actNumber}] has no state entry — will show ❌ on dashboard");
                $issues++;
            }

            // 3. Duplicate act_folder_id + name
            $key = $actFolderId.'::'.$name;
            if (isset($seen[$key])) {
                $actNumber = $folderIndex[$actFolderId]['act_number'] ?? $actFolderId;
                $this->warn("DUPLICATE: subfolder [{$name}] on ACT [{$actNumber}] registered under multiple IDs: [{$seen[$key]}] and [{$subfolderId}]");
                $issues++;
            } else {
                $seen[$key] = $subfolderId;
            }
        }

        if ($issues === 0) {
            $this->info('✅ Drive state is healthy — no issues found.');

            return self::SUCCESS;
        }

        if ($this->option('fix')) {
            $this->info("Found {$issues} issue(s) — reseeding now...");
            $count = $this->watcher->seedFromAirtable();
            $this->info("Seeded {$count} ACT folders. Re-running health check...");

            // Re-check after seed
            $this->state = app(DriveWatcherStateRepository::class);

            return $this->handle();
        }

        $this->error("Found {$issues} issue(s) in drive-watcher-state.json — run php artisan vigil:drive-health --fix to auto-reseed.");

        return self::FAILURE;
    }
}
