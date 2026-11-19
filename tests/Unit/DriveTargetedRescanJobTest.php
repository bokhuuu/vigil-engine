<?php

declare(strict_types=1);

use App\Jobs\DriveTargetedRescanJob;
use App\Services\DriveWatcherService;
use Tests\TestCase;

uses(TestCase::class);

test('targeted rescan: calls rescanActs with the given ACT folder IDs', function () {
    $actFolderIds = ['folder_abc', 'folder_xyz'];

    $watcher = Mockery::mock(DriveWatcherService::class);
    $watcher->expects('rescanActs')
        ->once()
        ->with($actFolderIds)
        ->andReturn(['checked' => 2, 'updated' => 2]);

    (new DriveTargetedRescanJob($actFolderIds))->handle($watcher);
});

test('targeted rescan: does nothing when ACT folder IDs list is empty', function () {
    $watcher = Mockery::mock(DriveWatcherService::class);
    $watcher->expects('rescanActs')->never();

    (new DriveTargetedRescanJob([]))->handle($watcher);
});

test('targeted rescan: passes all IDs unchanged to rescanActs', function () {
    $actFolderIds = ['folder_1', 'folder_2', 'folder_3', 'folder_4'];

    $watcher = Mockery::mock(DriveWatcherService::class);
    $watcher->expects('rescanActs')
        ->once()
        ->with(Mockery::on(fn (array $ids) => $ids === $actFolderIds))
        ->andReturn(['checked' => 4, 'updated' => 1]);

    (new DriveTargetedRescanJob($actFolderIds))->handle($watcher);
});
