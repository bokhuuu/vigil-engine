<?php

declare(strict_types=1);

use App\Jobs\DriveDrawingsUploadJob;
use App\Repositories\DriveWatcherStateRepository;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

it('skips file that already exists in skip mode', function () {
    $folderIndex = [
        'folder-act-123' => ['act_number' => '123'],
    ];

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->shouldReceive('getFolderIndex')->once()->andReturn($folderIndex);
    $stateRepo->shouldNotReceive('setSubfolderState');

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('listChildFolders')
        ->once()
        ->with('folder-act-123')
        ->andReturn(['შიდა ნახაზები' => 'drawings-folder-id']);
    $drive->shouldReceive('listFilesInFolder')
        ->once()
        ->with('drawings-folder-id')
        ->andReturn([['name' => 'plan.dwg']]);
    $drive->shouldNotReceive('uploadFile');

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->shouldReceive('sendToGroup')->once();

    Cache::shouldReceive('forget')->once()->with('upload_drawings_running');

    // Build a minimal ZIP with one file: 123/plan.dwg
    $tmpZip = tempnam(sys_get_temp_dir(), 'test_drawings_');
    $zip = new ZipArchive;
    $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('123/plan.dwg', 'fake dwg content');
    $zip->close();

    $job = new DriveDrawingsUploadJob('skip', $tmpZip);
    $job->handle($stateRepo, $drive, $telegram);

    @unlink($tmpZip);
});

it('uploads new file in skip mode', function () {
    $folderIndex = [
        'folder-act-456' => ['act_number' => '456'],
    ];

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->shouldReceive('getFolderIndex')->once()->andReturn($folderIndex);
    $stateRepo->shouldReceive('setSubfolderState')->once()->with('drawings-folder-id', 'full');

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('listChildFolders')
        ->once()
        ->with('folder-act-456')
        ->andReturn(['შიდა ნახაზები' => 'drawings-folder-id']);
    $drive->shouldReceive('listFilesInFolder')
        ->once()
        ->with('drawings-folder-id')
        ->andReturn([]); // folder is empty
    $drive->shouldReceive('uploadFile')
        ->once()
        ->with('drawings-folder-id', 'newplan.dwg', Mockery::any(), 'application/acad');

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->shouldReceive('sendToGroup')->once()->with(Mockery::on(
        fn (string $msg) => str_contains($msg, '✅ ატვირთულია: 1')
    ));

    Cache::shouldReceive('forget')->once()->with('upload_drawings_running');

    $tmpZip = tempnam(sys_get_temp_dir(), 'test_drawings_');
    $zip = new ZipArchive;
    $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('456/newplan.dwg', 'fake dwg content');
    $zip->close();

    $job = new DriveDrawingsUploadJob('skip', $tmpZip);
    $job->handle($stateRepo, $drive, $telegram);

    @unlink($tmpZip);
});

it('creates შიდა ნახაზები folder if it does not exist', function () {
    $folderIndex = [
        'folder-act-789' => ['act_number' => '789'],
    ];

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->shouldReceive('getFolderIndex')->once()->andReturn($folderIndex);
    $stateRepo->shouldReceive('setSubfolderState')->once()->with('new-drawings-id', 'full');

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('listChildFolders')
        ->once()
        ->with('folder-act-789')
        ->andReturn([]); // no subfolders yet
    $drive->shouldReceive('findOrCreateFolder')
        ->once()
        ->with('შიდა ნახაზები', 'folder-act-789')
        ->andReturn('new-drawings-id');
    $drive->shouldReceive('listFilesInFolder')
        ->once()
        ->with('new-drawings-id')
        ->andReturn([]);
    $drive->shouldReceive('uploadFile')
        ->once()
        ->with('new-drawings-id', 'section.pdf', Mockery::any(), 'application/pdf');

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->shouldReceive('sendToGroup')->once();

    Cache::shouldReceive('forget')->once()->with('upload_drawings_running');

    $tmpZip = tempnam(sys_get_temp_dir(), 'test_drawings_');
    $zip = new ZipArchive;
    $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('789/section.pdf', '%PDF-1.4 fake');
    $zip->close();

    $job = new DriveDrawingsUploadJob('skip', $tmpZip);
    $job->handle($stateRepo, $drive, $telegram);

    @unlink($tmpZip);
});

it('preserves nested subfolder structure', function () {
    $folderIndex = [
        'folder-act-100' => ['act_number' => '100'],
    ];

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->shouldReceive('getFolderIndex')->once()->andReturn($folderIndex);
    $stateRepo->shouldReceive('setSubfolderState')->once();

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('listChildFolders')
        ->once()
        ->with('folder-act-100')
        ->andReturn(['შიდა ნახაზები' => 'drawings-root-id']);
    $drive->shouldReceive('ensureSubfolderPathFromId')
        ->once()
        ->with('drawings-root-id', 'სქემები')
        ->andReturn('schemes-subfolder-id');
    $drive->shouldReceive('listFilesInFolder')
        ->once()
        ->with('schemes-subfolder-id')
        ->andReturn([]);
    $drive->shouldReceive('uploadFile')
        ->once()
        ->with('schemes-subfolder-id', 'electrical.dwg', Mockery::any(), 'application/acad');

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->shouldReceive('sendToGroup')->once();

    Cache::shouldReceive('forget')->once()->with('upload_drawings_running');

    $tmpZip = tempnam(sys_get_temp_dir(), 'test_drawings_');
    $zip = new ZipArchive;
    $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('100/სქემები/electrical.dwg', 'fake dwg');
    $zip->close();

    $job = new DriveDrawingsUploadJob('skip', $tmpZip);
    $job->handle($stateRepo, $drive, $telegram);

    @unlink($tmpZip);
});

it('aborts when folder_index is empty', function () {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->shouldReceive('getFolderIndex')->once()->andReturn([]);

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldNotReceive('uploadFile');

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->shouldReceive('sendToGroup')->once()->with(Mockery::on(
        fn (string $msg) => str_contains($msg, 'folder_index ცარიელია')
    ));

    Cache::shouldReceive('forget')->once()->with('upload_drawings_running');

    $tmpZip = tempnam(sys_get_temp_dir(), 'test_drawings_');
    $zip = new ZipArchive;
    $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('123/plan.dwg', 'content');
    $zip->close();

    $job = new DriveDrawingsUploadJob('skip', $tmpZip);
    $job->handle($stateRepo, $drive, $telegram);

    @unlink($tmpZip);
});
