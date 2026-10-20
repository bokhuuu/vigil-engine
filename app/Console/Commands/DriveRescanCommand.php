<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DriveWatcherService;
use Illuminate\Console\Command;

class DriveRescanCommand extends Command
{
    protected $signature = 'vigil:drive-rescan';

    protected $description = 'Full rescan — checks every known subfolder for files and corrects states + colors.';

    public function __construct(
        private DriveWatcherService $watcher,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting full Drive rescan…');

        $result = $this->watcher->rescan();

        $this->info("Done. Checked: {$result['checked']}, Updated: {$result['updated']}");

        return self::SUCCESS;
    }
}
