<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StorageCleanup extends Command
{
    protected $signature = 'storage:cleanup';

    protected $description = 'Delete stale download ZIPs older than 2 hours from storage/app/downloads';

    public function handle(): int
    {
        $dir = storage_path('app/downloads');

        if (! is_dir($dir)) {
            $this->info('No downloads directory found — nothing to clean.');

            return self::SUCCESS;
        }

        $cutoff = now()->subHours(24)->timestamp;
        $deleted = 0;

        foreach (glob($dir.'/*.zip') ?: [] as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Deleted {$deleted} stale ZIP(s) from storage/app/downloads.");
        } else {
            $this->info('No stale ZIPs found.');
        }

        return self::SUCCESS;
    }
}
