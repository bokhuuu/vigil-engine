<?php

declare(strict_types=1);

use App\Repositories\DriveWatcherStateRepository;
use App\Services\AirtableService;
use App\Services\DriveEventService;
use App\Services\DriveWatcherService;
use App\Services\GoogleDriveService;
use App\Services\ManagerNotifier;

function makeWatcherService(
    DriveWatcherStateRepository $state,
    ?AirtableService $airtable = null,
    ?GoogleDriveService $drive = null,
    ?ManagerNotifier $notifier = null,
    ?DriveEventService $events = null,
): DriveWatcherService {
    $airtable ??= Mockery::mock(AirtableService::class);
    $drive ??= Mockery::mock(GoogleDriveService::class);
    $notifier ??= Mockery::mock(ManagerNotifier::class);
    $events ??= Mockery::mock(DriveEventService::class);

    return new DriveWatcherService($airtable, $drive, $state, $notifier, $events);
}

beforeEach(function (): void {
    $this->path = sys_get_temp_dir().'/vigil-watcher-test-'.uniqid().'.json';
    $this->state = new DriveWatcherStateRepository($this->path);
});

afterEach(function (): void {
    if (file_exists($this->path)) {
        unlink($this->path);
    }
});

it('skips changed ids not in subfolder index', function (): void {
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('getFileMetadata')->with('unknown-id')->andReturn(null);

    $service = makeWatcherService($this->state, drive: $drive);
    $service->process(['unknown-id']);

    expect($this->state->getSubfolderState('unknown-id'))->toBeNull();
});

it('ticks checkbox when files appear in subfolder', function (): void {
    $this->state->addActFolder('act-folder-1', '15', 'recABC');
    $this->state->addSubfolder('sub-1', 'act-folder-1', 'ფოტოები');
    $this->state->setSubfolderState('sub-1', 'empty');

    $events = Mockery::mock(DriveEventService::class);
    $events->shouldReceive('fileAdded')->with('15', 'ფოტოები')->once();

    $airtable = Mockery::mock(AirtableService::class);
    $airtable->shouldReceive('getActByNumber')->with('15')->andReturn([
        'id' => 'recABC',
        'fields' => ['💼 საქაღალდე' => 'https://drive.google.com/drive/folders/act-folder-1'],
    ])->byDefault();
    $airtable->shouldReceive('getManagers')->andReturn([])->byDefault();

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('subfolderHasFiles')->with('sub-1')->andReturn(true);
    $drive->shouldReceive('setFolderColorAsUser')->andReturn(null)->byDefault();

    $service = makeWatcherService($this->state, $airtable, $drive, events: $events);
    $service->process(['sub-1']);

    expect($this->state->getSubfolderState('sub-1'))->toBe('full');
});

it('starts grace timer when folder goes empty', function (): void {
    $this->state->addActFolder('act-folder-1', '15', 'recABC');
    $this->state->addSubfolder('sub-1', 'act-folder-1', 'ფოტოები');
    $this->state->setSubfolderState('sub-1', 'full');

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('subfolderHasFiles')->with('sub-1')->andReturn(false);
    $drive->shouldReceive('setFolderColorAsUser')->andReturn(null)->byDefault();

    $airtable = Mockery::mock(AirtableService::class);
    $airtable->shouldReceive('getActByNumber')->andReturn(null)->byDefault();
    $airtable->shouldReceive('getManagers')->andReturn([])->byDefault();

    $service = makeWatcherService($this->state, $airtable, $drive);
    $service->process(['sub-1']);

    $newState = $this->state->getSubfolderState('sub-1');
    expect($newState)->toStartWith('grace:');
});

it('does not tick checkbox in dry run mode', function (): void {
    $this->state->addActFolder('act-folder-1', '15', 'recABC');
    $this->state->addSubfolder('sub-1', 'act-folder-1', 'ფოტოები');
    $this->state->setSubfolderState('sub-1', 'empty');

    $act = [
        'id' => 'recABC',
        'fields' => ['💼 საქაღალდე' => 'https://drive.google.com/drive/folders/act-folder-1'],
    ];

    $events = Mockery::mock(DriveEventService::class);
    $events->shouldNotReceive('fileAdded');

    $airtable = Mockery::mock(AirtableService::class);
    $airtable->shouldReceive('getActByNumber')->with('15')->andReturn($act)->byDefault();
    $airtable->shouldReceive('getManagers')->andReturn([])->byDefault();

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('subfolderHasFiles')->with('sub-1')->andReturn(true);
    $drive->shouldReceive('setFolderColorAsUser')->andReturn(null)->byDefault();

    $service = makeWatcherService($this->state, $airtable, $drive, events: $events);
    $service->process(['sub-1'], dryRun: true);

    expect($this->state->getSubfolderState('sub-1'))->toBe('empty');
});
