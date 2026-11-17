<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\AirtableService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AirtableSnapshotJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 1;

    public function handle(AirtableService $airtable): void
    {
        Log::info('AirtableSnapshotJob: refreshing dashboard snapshot');

        $fresh = [
            'acts' => $airtable->getAllActs(),
            'fetched_at' => now()->timestamp,
        ];

        Cache::put('dashboard_snapshot', $fresh, 35 * 60);
        Cache::forever('dashboard_snapshot_last_good', $fresh);

        Log::info('AirtableSnapshotJob: done — '.count($fresh['acts']).' ACTs cached');
    }
}
