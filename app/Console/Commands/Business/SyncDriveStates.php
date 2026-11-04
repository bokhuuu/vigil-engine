<?php

declare(strict_types=1);

namespace App\Console\Commands\Business;

use App\Business\Models\Business;
use App\Business\Services\BusinessDriveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDriveStates extends Command
{
    protected $signature = 'business:sync-drive-states';

    protected $description = 'Sync Drive subfolder file-presence states to DB for all businesses';

    public function __construct(private readonly BusinessDriveService $driveService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $businesses = Business::on('business')
            ->whereNotNull('drive_folder_url')
            ->get();

        if ($businesses->isEmpty()) {
            $this->info('No businesses with Drive folders found.');

            return;
        }

        $processed = 0;

        foreach ($businesses as $business) {
            try {
                $this->driveService->syncSubfolderStates($business);
                $processed++;
            } catch (\Throwable $e) {
                Log::error("SyncDriveStates: failed for [{$business->name}]", [
                    'error' => $e->getMessage(),
                ]);
                $this->warn("Failed: {$business->name} — {$e->getMessage()}");
            }
        }

        $this->info("Synced {$processed} of {$businesses->count()} businesses.");
    }
}
