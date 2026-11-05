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
    $telegram->allows('sendToGroup')->withAnyArgs();

    makeJob()->handle($stateRepo, $drive, $telegram);
}

// ── tests ──────────────────────────────────────────────────────────────────

test('clears download_running and aborts when folder_index is empty', function () {
    Cache::put('download_scan_running:all', true, 60);

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn([]);

    $drive = Mockery::mock(GoogleDriveService::class);
    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->never();

    makeJob()->handle($stateRepo, $drive, $telegram);

    expect(Cache::get('download_scan_running:all'))->toBeNull();
});

test('skips ACT folders that have no სკანი subfolder', function () {
    Cache::put('download_scan_running:all', true, 60);

    runJob(
        folderIndex: makeFolderIndex(['folder1' => '1-1']),
        childFolders: ['folder1' => ['ფოტოები' => 'photo_id']],
    );

    expect(Cache::get('download_scan_running:all'))->toBeNull();
    expect(Cache::has('download_scan_ready_path:all'))->toBeFalse();
});

test('creates a ZIP and sets cache keys when files are downloaded', function () {
    Cache::put('download_scan_running:all', true, 60);

    runJob(
        folderIndex: makeFolderIndex(['folder1' => '1-1']),
        childFolders: ['folder1' => ['სკანი' => 'skani1']],
        filesInFolder: ['skani1' => [['id' => 'f1', 'name' => 'scan.pdf', 'mimeType' => 'application/pdf']]],
        fileContents: ['f1' => '%PDF binary content'],
    );

    expect(Cache::get('download_scan_running:all'))->toBeNull();
    expect(Cache::has('download_scan_ready_path:all'))->toBeTrue();
    expect(Cache::has('download_scan_ready_name:all'))->toBeTrue();

    $zipPath = Cache::get('download_scan_ready_path:all');
    expect(file_exists($zipPath))->toBeTrue();

    // clean up
    @unlink($zipPath);
});

test('ZIP contains file flat (no ACT subfolder)', function () {
    runJob(
        folderIndex: makeFolderIndex(['folder1' => '2-3']),
        childFolders: ['folder1' => ['სკანი' => 'skani1']],
        filesInFolder: ['skani1' => [['id' => 'f1', 'name' => 'scan.pdf', 'mimeType' => 'application/pdf']]],
        fileContents: ['f1' => 'content'],
    );

    $zipPath = Cache::get('download_scan_ready_path:all');
    $zip = new ZipArchive;
    $zip->open($zipPath);

    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $zip->close();

    expect($names)->toContain('ACT_2-3_scan.pdf');

    @unlink($zipPath);
});

test('continues when a single file download fails', function () {
    Cache::put('download_scan_running:all', true, 60);

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
    $telegram->allows('sendToGroup');

    makeJob()->handle($stateRepo, $drive, $telegram);

    expect(Cache::get('download_scan_running:all'))->toBeNull();
    expect(Cache::has('download_scan_ready_path:all'))->toBeTrue();

    @unlink(Cache::get('download_scan_ready_path:all'));
});

test('clears download_running after completion', function () {
    Cache::put('download_scan_running:all', true, 60);

    runJob(
        folderIndex: makeFolderIndex(['folder1' => '1-1']),
        childFolders: ['folder1' => ['სკანი' => 'skani1']],
        filesInFolder: ['skani1' => [['id' => 'f1', 'name' => 'a.pdf', 'mimeType' => 'application/pdf']]],
        fileContents: ['f1' => 'data'],
    );

    expect(Cache::get('download_scan_running:all'))->toBeNull();

    @unlink(Cache::get('download_scan_ready_path:all'));
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
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'სკანების გადმოწერა დასრულდა'))
    );

    makeJob()->handle($stateRepo, $drive, $telegram);

    @unlink(Cache::get('download_scan_ready_path:all'));
});

test('skips non-PDF files in სკანი and never downloads them', function () {
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listChildFolders')->andReturn(['სკანი' => 'skani1']);
    $drive->allows('listFilesInFolder')->andReturn([
        ['id' => 'f1', 'name' => 'scan.pdf',  'mimeType' => 'application/pdf'],
        ['id' => 'f2', 'name' => 'photo.jpg', 'mimeType' => 'image/jpeg'],
    ]);
    $drive->expects('downloadFile')->once()->with('f1')->andReturn('content');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makeFolderIndex(['folder1' => '1-1'])
    );

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('sendToGroup')->withAnyArgs();

    makeJob()->handle($stateRepo, $drive, $telegram);

    expect(Cache::has('download_scan_ready_path:all'))->toBeTrue();

    @unlink(Cache::get('download_scan_ready_path:all'));
});
