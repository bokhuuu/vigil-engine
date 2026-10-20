<?php

declare(strict_types=1);

use App\Repositories\DriveWatcherStateRepository;
use App\Services\AirtableService;
use App\Services\DriveEventService;
use App\Services\DriveWatcherService;
use App\Services\GoogleDriveService;
use App\Services\ManagerNotifier;

function makeRescanState(array $data): DriveWatcherStateRepository
{
    $path = storage_path('app/google/test-rescan-state.json');
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, json_encode($data));

    return new DriveWatcherStateRepository($path);
}

it('marks empty subfolder as full when it has files', function () {
    $state = makeRescanState([
        'page_token' => '123',
        'folder_index' => ['act-folder-1' => ['act_number' => '131', 'act_record_id' => 'rec1']],
        'subfolder_index' => ['sub1' => ['act_folder_id' => 'act-folder-1', 'name' => 'სკანი']],
        'subfolder_states' => ['sub1' => 'empty'],
        'folder_colors' => [],
    ]);

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('subfolderHasFiles')->with('sub1')->andReturn(true);
    $drive->shouldReceive('getFileMetadata')->andReturn(null);
    $drive->shouldReceive('setFolderColorAsUser')->andReturn(null);

    $airtable = Mockery::mock(AirtableService::class);
    $airtable->shouldReceive('getActByNumber')->andReturn(['fields' => []]);

    $events = Mockery::mock(DriveEventService::class);
    $events->shouldReceive('fileAdded')->once();

    $service = new DriveWatcherService(
        $airtable,
        $drive,
        $state,
        Mockery::mock(ManagerNotifier::class),
        $events,
    );

    $result = $service->rescan();

    expect($result['checked'])->toBe(1)
        ->and($result['updated'])->toBe(1)
        ->and($state->getSubfolderState('sub1'))->toBe('full');
});

it('skips subfolders in grace period', function () {
    $state = makeRescanState([
        'page_token' => '123',
        'folder_index' => ['act-folder-1' => ['act_number' => '131', 'act_record_id' => 'rec1']],
        'subfolder_index' => ['sub1' => ['act_folder_id' => 'act-folder-1', 'name' => 'სკანი']],
        'subfolder_states' => ['sub1' => 'grace:9999999999999'],
        'folder_colors' => [],
    ]);

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldNotReceive('subfolderHasFiles');

    $service = new DriveWatcherService(
        Mockery::mock(AirtableService::class),
        $drive,
        $state,
        Mockery::mock(ManagerNotifier::class),
        Mockery::mock(DriveEventService::class),
    );

    $result = $service->rescan();

    expect($result['checked'])->toBe(0)
        ->and($result['updated'])->toBe(0);
});

it('returns zero counts when subfolder index is empty', function () {
    $state = makeRescanState([
        'page_token' => '123',
        'folder_index' => [],
        'subfolder_index' => [],
        'subfolder_states' => [],
        'folder_colors' => [],
    ]);

    $service = new DriveWatcherService(
        Mockery::mock(AirtableService::class),
        Mockery::mock(GoogleDriveService::class),
        $state,
        Mockery::mock(ManagerNotifier::class),
        Mockery::mock(DriveEventService::class),
    );

    $result = $service->rescan();

    expect($result['checked'])->toBe(0)
        ->and($result['updated'])->toBe(0);
});
