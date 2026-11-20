<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\DriveWatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DriveTargetedRescanJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(30);
    }

    /**
     * @param  string[]  $actFolderIds  Drive folder IDs of the ACTs that were uploaded to.
     */
    public function __construct(
        public readonly array $actFolderIds,
    ) {}

    public function handle(DriveWatcherService $watcher): void
    {
        if (empty($this->actFolderIds)) {
            Log::info('DriveTargetedRescanJob: no ACT folder IDs — nothing to do');

            return;
        }

        Log::info('DriveTargetedRescanJob: starting rescan for '.count($this->actFolderIds).' ACT(s)');

        $result = $watcher->rescanActs($this->actFolderIds);

        Log::info("DriveTargetedRescanJob: done — checked: {$result['checked']}, updated: {$result['updated']}");
    }
}
