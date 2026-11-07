<?php

declare(strict_types=1);

namespace App\Console\Commands\Business;

use App\Business\Models\Correspondence;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class CheckOrphanedCorrespondence extends Command
{
    protected $signature = 'business:check-orphaned-correspondence';

    protected $description = 'Report correspondence rows whose Drive folder no longer exists';

    public function __construct(private readonly GoogleDriveService $drive)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $rows = Correspondence::on('business')
            ->whereNotNull('drive_folder_url')
            ->get(['id', 'business_id', 'round', 'drive_folder_url']);

        $orphaned = [];

        foreach ($rows as $row) {
            $folderId = $this->drive->getFolderIdFromUrl($row->drive_folder_url);

            if ($folderId === null || $folderId === '') {
                continue;
            }

            $meta = $this->drive->getFileMetadata($folderId);

            if ($meta === null) {
                $orphaned[] = $row;
            }
        }

        if (empty($orphaned)) {
            $this->info('No orphaned correspondence rows found.');

            return self::SUCCESS;
        }

        $this->warn(count($orphaned).' orphaned row(s) found:');

        foreach ($orphaned as $row) {
            $this->line("  id={$row->id} business_id={$row->business_id} round={$row->round} → {$row->drive_folder_url}");
        }

        return self::SUCCESS;
    }
}
