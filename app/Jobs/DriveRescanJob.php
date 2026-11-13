<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\DriveWatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DriveRescanJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 7200;

    public int $tries = 1;

    public function handle(DriveWatcherService $watcher): void
    {
        Log::info('DriveRescanJob: starting full rescan');

        $result = $watcher->rescan();

        Log::info("DriveRescanJob: done — checked: {$result['checked']}, updated: {$result['updated']}");

        Cache::forget('rescan_running');

        Cache::forget('drive_states_map');
    }
}
