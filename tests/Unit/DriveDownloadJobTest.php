<?php

declare(strict_types=1);

use App\Jobs\DriveDownloadJob;
use App\Repositories\DriveWatcherStateRepository;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

// ── helpers ────────────────────────────────────────────────────────────────

function makeFolderIndex(array $entries = []): array
{
    // $entries: [folderId => actNumber]
    $index = [];
    foreach ($entries as $id => $number) {
        $index[$id] = ['act_number' => $number, 'act_record_id' => 'rec_'.$number];
    }

    return $index;
}

function makeJob(): DriveDownloadJob
{
    return new DriveDownloadJob;
}

function runJob(
    array $folderIndex,
    array $childFolders = [],   // folderId => ['სკანი' => subFolderId]
    array $filesInFolder = [],  // subFolderId => [['id'=>..,'name'=>..]]
    array $fileContents = [],   // fileId => content string
): void {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn($folderIndex);

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listChildFolders')->andReturnUsing(
        fn (string $id) => $childFolders[$id] ?? []
    );
    $drive->allows('listFilesInFolder')->andReturnUsing(
        fn (string $id) => $filesInFolder[$id] ?? []
    );
    $drive->allows('downloadFile')->andReturnUsing(
        fn (string $id) => $fileContents[$id] ?? 'binary'
    );

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('send')->withAnyArgs();

    makeJob()->handle($stateRepo, $drive, $telegram);
}

// ── tests ──────────────────────────────────────────────────────────────────

test('clears download_running and aborts when folder_index is empty', function () {
    Cache::put('download_running', true, 60);

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn([]);

    $drive = Mockery::mock(GoogleDriveService::class);
    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('send')->never();

    makeJob()->handle($stateRepo, $drive, $telegram);

    expect(Cache::get('download_running'))->toBeNull();
});

test('skips ACT folders that have no სკანი subfolder', function () {
    Cache::put('download_running', true, 60);

    runJob(
        folderIndex: makeFolderIndex(['folder1' => '1-1']),
        childFolders: ['folder1' => ['ფოტოები' => 'photo_id']], // no სკანი
    );

    // job completed — download_running cleared, no ZIP with files in it
    expect(Cache::get('download_running'))->toBeNull();
    expect(Cache::has('download_ready_path'))->toBeFalse();
});

test('creates a ZIP and sets cache keys when files are downloaded', function () {
    Cache::put('download_running', true, 60);

    runJob(
        folderIndex: makeFolderIndex(['folder1' => '1-1']),
        childFolders: ['folder1' => ['სკანი' => 'skani1']],
        filesInFolder: ['skani1' => [['id' => 'f1', 'name' => 'scan.pdf', 'mimeType' => 'application/pdf']]],
        fileContents: ['f1' => '%PDF binary content'],
    );

    expect(Cache::get('download_running'))->toBeNull();
    expect(Cache::has('download_ready_path'))->toBeTrue();
    expect(Cache::has('download_ready_name'))->toBeTrue();

    $zipPath = Cache::get('download_ready_path');
    expect(file_exists($zipPath))->toBeTrue();

    // clean up
    @unlink($zipPath);
});

test('ZIP contains file nested under ACT number directory', function () {
    runJob(
        folderIndex: makeFolderIndex(['folder1' => '2-3']),
        childFolders: ['folder1' => ['სკანი' => 'skani1']],
        filesInFolder: ['skani1' => [['id' => 'f1', 'name' => 'scan.pdf', 'mimeType' => 'application/pdf']]],
        fileContents: ['f1' => 'content'],
    );

    $zipPath = Cache::get('download_ready_path');
    $zip = new ZipArchive;
    $zip->open($zipPath);

    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $zip->close();

    expect($names)->toContain('2-3/scan.pdf');

    @unlink($zipPath);
});

test('continues when a single file download fails', function () {
    Cache::put('download_running', true, 60);

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makeFolderIndex(['folder1' => '1-1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listChildFolders')->andReturn(['სკანი' => 'skani1']);
    $drive->allows('listFilesInFolder')->andReturn([
        ['id' => 'f1', 'name' => 'ok.pdf',   'mimeType' => 'application/pdf'],
        ['id' => 'f2', 'name' => 'fail.pdf',  'mimeType' => 'application/pdf'],
    ]);
    $drive->allows('downloadFile')->andReturnUsing(function (string $id) {
        if ($id === 'f2') {
            throw new RuntimeException('Drive API error');
        }

        return 'ok content';
    });

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('send');

    makeJob()->handle($stateRepo, $drive, $telegram);

    expect(Cache::get('download_running'))->toBeNull();
    expect(Cache::has('download_ready_path'))->toBeTrue();

    @unlink(Cache::get('download_ready_path'));
});

test('clears download_running after completion', function () {
    Cache::put('download_running', true, 60);

    runJob(
        folderIndex: makeFolderIndex(['folder1' => '1-1']),
        childFolders: ['folder1' => ['სკანი' => 'skani1']],
        filesInFolder: ['skani1' => [['id' => 'f1', 'name' => 'a.pdf', 'mimeType' => 'application/pdf']]],
        fileContents: ['f1' => 'data'],
    );

    expect(Cache::get('download_running'))->toBeNull();

    @unlink(Cache::get('download_ready_path'));
});

test('sends Telegram message after successful download', function () {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makeFolderIndex(['folder1' => '1-1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listChildFolders')->andReturn(['სკანი' => 'skani1']);
    $drive->allows('listFilesInFolder')->andReturn([
        ['id' => 'f1', 'name' => 'scan.pdf', 'mimeType' => 'application/pdf'],
    ]);
    $drive->allows('downloadFile')->andReturn('content');

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('send')->once()->with(
        Mockery::any(),
        Mockery::on(fn (string $msg) => str_contains($msg, 'სკანების გადმოწერა დასრულდა'))
    );

    makeJob()->handle($stateRepo, $drive, $telegram);

    @unlink(Cache::get('download_ready_path'));
});
