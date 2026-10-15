<?php

declare(strict_types=1);

use App\Repositories\DriveWatcherStateRepository;
use App\Services\DriveChangesReader;
use App\Services\GoogleDriveService;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\Change;
use Google\Service\Drive\ChangeList;
use Google\Service\Drive\Resource\Changes;

function makeDriveService(mixed $mockChanges): GoogleDriveService
{
    $mockDrive = Mockery::mock(GoogleDrive::class);
    $mockDrive->changes = $mockChanges;

    $driveService = Mockery::mock(GoogleDriveService::class);
    $driveService->shouldReceive('getUserDrive')->andReturn($mockDrive);

    return $driveService;
}

it('returns empty array when no page token is stored', function (): void {
    $path = storage_path('app/google/drive-watcher-state-test.json');
    $state = new DriveWatcherStateRepository($path);
    $driveService = Mockery::mock(GoogleDriveService::class);
    $driveService->shouldNotReceive('getUserDrive');

    $reader = new DriveChangesReader($driveService, $state);

    expect($reader->getChangedIds())->toBe([]);

    if (file_exists($path)) {
        unlink($path);
    }
});

it('returns changed file ids and updates page token', function (): void {
    $path = storage_path('app/google/drive-watcher-state-test.json');
    $state = new DriveWatcherStateRepository($path);
    $state->setPageToken('token-start');

    $change1 = Mockery::mock(Change::class);
    $change1->shouldReceive('getFileId')->andReturn('file-id-1');
    $change1->shouldReceive('getFile')->andReturn(null);

    $change2 = Mockery::mock(Change::class);
    $change2->shouldReceive('getFileId')->andReturn('file-id-2');
    $change2->shouldReceive('getFile')->andReturn(null);

    $changeList = Mockery::mock(ChangeList::class);
    $changeList->shouldReceive('getChanges')->andReturn([$change1, $change2]);
    $changeList->shouldReceive('getNextPageToken')->andReturn(null);
    $changeList->shouldReceive('getNewStartPageToken')->andReturn('token-next');

    $mockChanges = Mockery::mock(Changes::class);
    $mockChanges->shouldReceive('listChanges')
        ->once()
        ->with('token-start', Mockery::any())
        ->andReturn($changeList);

    $reader = new DriveChangesReader(makeDriveService($mockChanges), $state);
    $ids = $reader->getChangedIds();

    expect($ids)->toBe(['file-id-1', 'file-id-2']);
    expect($state->getPageToken())->toBe('token-next');

    unlink($path);
});

it('returns empty array and keeps token on api failure', function (): void {
    $path = storage_path('app/google/drive-watcher-state-test.json');
    $state = new DriveWatcherStateRepository($path);
    $state->setPageToken('token-start');

    $mockChanges = Mockery::mock(Changes::class);
    $mockChanges->shouldReceive('listChanges')
        ->once()
        ->andThrow(new Exception('API error'));

    $reader = new DriveChangesReader(makeDriveService($mockChanges), $state);
    $ids = $reader->getChangedIds();

    expect($ids)->toBe([]);
    expect($state->getPageToken())->toBe('token-start');

    unlink($path);
});
