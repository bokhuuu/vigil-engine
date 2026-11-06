<?php

declare(strict_types=1);

use App\Jobs\DrivePhotosDownloadJob;
use App\Repositories\DriveWatcherStateRepository;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

// ── helpers ────────────────────────────────────────────────────────────────

function makePhotosFolderIndex(array $entries = []): array
{
    $index = [];
    foreach ($entries as $id => $number) {
        $index[$id] = ['act_number' => $number, 'act_record_id' => 'rec_'.$number];
    }

    return $index;
}

function makePhotosJob(?array $actRange = ['from' => 1, 'to' => 3]): DrivePhotosDownloadJob
{
    return new DrivePhotosDownloadJob($actRange);
}

function runPhotosJob(
    array $folderIndex,
    array $childFolders = [],
    array $allFiles = [],
    ?array $actRange = ['from' => 1, 'to' => 3],
): void {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn($folderIndex);

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listChildFolders')->andReturnUsing(
        fn (string $id) => $childFolders[$id] ?? []
    );
    $drive->allows('listAllInFolder')->andReturnUsing(
        fn (string $id) => $allFiles[$id] ?? []
    );
    $drive->allows('downloadFileTo')->andReturnUsing(
        function (string $fileId, string $tmpPath): void {
            file_put_contents($tmpPath, 'binary image content');
        }
    );

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('sendToGroup')->withAnyArgs();

    (new DrivePhotosDownloadJob($actRange))->handle($stateRepo, $drive, $telegram);
}

// ── tests ──────────────────────────────────────────────────────────────────

test('photos: clears running and aborts when folder_index is empty', function () {
    Cache::put('download_photos_running:1-3', true, 60);

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn([]);

    $drive = Mockery::mock(GoogleDriveService::class);
    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->never();

    makePhotosJob()->handle($stateRepo, $drive, $telegram);

    expect(Cache::get('download_photos_running:1-3'))->toBeNull();
});

test('photos: aborts when no ACTs match the range', function () {
    Cache::put('download_photos_running:1-3', true, 60);

    // folderIndex has ACT 10 — outside range 1-3
    runPhotosJob(
        folderIndex: makePhotosFolderIndex(['folder10' => '10']),
        actRange: ['from' => 1, 'to' => 3],
    );

    expect(Cache::get('download_photos_running:1-3'))->toBeNull();
    expect(Cache::has('download_photos_ready_path:1-3'))->toBeFalse();
});

test('photos: skips ACT folders that have no ფოტოები subfolder', function () {
    Cache::put('download_photos_running:1-3', true, 60);

    runPhotosJob(
        folderIndex: makePhotosFolderIndex(['folder1' => '1']),
        childFolders: ['folder1' => ['სკანი' => 'skani_id']],
        actRange: ['from' => 1, 'to' => 3],
    );

    expect(Cache::get('download_photos_running:1-3'))->toBeNull();
    expect(Cache::has('download_photos_ready_path:1-3'))->toBeFalse();
});

test('photos: skips non-image files in ფოტოები folder', function () {
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listChildFolders')->andReturn(['ფოტოები' => 'photos1']);
    $drive->allows('listAllInFolder')->andReturn([
        ['id' => 'f1', 'name' => 'doc.pdf',   'mimeType' => 'application/pdf', 'path' => 'doc.pdf'],
        ['id' => 'f2', 'name' => 'photo.jpg',  'mimeType' => 'image/jpeg',       'path' => 'photo.jpg'],
    ]);
    $drive->expects('downloadFileTo')->once()->withArgs(
        fn (string $id) => $id === 'f2'
    )->andReturnUsing(function (string $id, string $path): void {
        file_put_contents($path, 'img');
    });

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makePhotosFolderIndex(['folder1' => '1'])
    );

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('sendToGroup')->withAnyArgs();

    (new DrivePhotosDownloadJob(['from' => 1, 'to' => 3]))->handle($stateRepo, $drive, $telegram);

    expect(Cache::has('download_photos_ready_path:1-3'))->toBeTrue();
    @unlink(Cache::get('download_photos_ready_path:1-3'));
});

test('photos: creates ZIP and sets cache keys when images are downloaded', function () {
    Cache::put('download_photos_running:1-3', true, 60);

    runPhotosJob(
        folderIndex: makePhotosFolderIndex(['folder1' => '1']),
        childFolders: ['folder1' => ['ფოტოები' => 'photos1']],
        allFiles: ['photos1' => [
            ['id' => 'img1', 'name' => 'photo.jpg', 'mimeType' => 'image/jpeg', 'path' => 'photo.jpg'],
        ]],
        actRange: ['from' => 1, 'to' => 3],
    );

    expect(Cache::get('download_photos_running:1-3'))->toBeNull();
    expect(Cache::has('download_photos_ready_path:1-3'))->toBeTrue();
    expect(Cache::has('download_photos_ready_name:1-3'))->toBeTrue();

    $zipPath = Cache::get('download_photos_ready_path:1-3');
    expect(file_exists($zipPath))->toBeTrue();

    @unlink($zipPath);
});

test('photos: ZIP uses nested path ACT_number/image.jpg', function () {
    runPhotosJob(
        folderIndex: makePhotosFolderIndex(['folder2' => '2']),
        childFolders: ['folder2' => ['ფოტოები' => 'photos2']],
        allFiles: ['photos2' => [
            ['id' => 'img1', 'name' => 'shot.jpg', 'mimeType' => 'image/jpeg', 'path' => 'shot.jpg'],
        ]],
        actRange: ['from' => 1, 'to' => 3],
    );

    $zipPath = Cache::get('download_photos_ready_path:1-3');
    $zip = new ZipArchive;
    $zip->open($zipPath);

    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $zip->close();

    expect($names)->toContain('ACT_2/shot.jpg');

    @unlink($zipPath);
});

test('photos: continues when a single file download fails', function () {
    Cache::put('download_photos_running:1-3', true, 60);

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makePhotosFolderIndex(['folder1' => '1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listChildFolders')->andReturn(['ფოტოები' => 'photos1']);
    $drive->allows('listAllInFolder')->andReturn([
        ['id' => 'img1', 'name' => 'ok.jpg',   'mimeType' => 'image/jpeg', 'path' => 'ok.jpg'],
        ['id' => 'img2', 'name' => 'fail.jpg',  'mimeType' => 'image/jpeg', 'path' => 'fail.jpg'],
    ]);
    $drive->allows('downloadFileTo')->andReturnUsing(function (string $id, string $path): void {
        if ($id === 'img2') {
            throw new RuntimeException('Drive API error');
        }
        file_put_contents($path, 'ok');
    });

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('sendToGroup');

    (new DrivePhotosDownloadJob(['from' => 1, 'to' => 3]))->handle($stateRepo, $drive, $telegram);

    expect(Cache::get('download_photos_running:1-3'))->toBeNull();
    expect(Cache::has('download_photos_ready_path:1-3'))->toBeTrue();

    @unlink(Cache::get('download_photos_ready_path:1-3'));
});

test('photos: clears running cache after completion', function () {
    Cache::put('download_photos_running:1-3', true, 60);

    runPhotosJob(
        folderIndex: makePhotosFolderIndex(['folder1' => '1']),
        childFolders: ['folder1' => ['ფოტოები' => 'photos1']],
        allFiles: ['photos1' => [
            ['id' => 'img1', 'name' => 'a.jpg', 'mimeType' => 'image/jpeg', 'path' => 'a.jpg'],
        ]],
        actRange: ['from' => 1, 'to' => 3],
    );

    expect(Cache::get('download_photos_running:1-3'))->toBeNull();

    @unlink(Cache::get('download_photos_ready_path:1-3'));
});

test('photos: sends Telegram message after successful download', function () {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makePhotosFolderIndex(['folder1' => '1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listChildFolders')->andReturn(['ფოტოები' => 'photos1']);
    $drive->allows('listAllInFolder')->andReturn([
        ['id' => 'img1', 'name' => 'photo.jpg', 'mimeType' => 'image/jpeg', 'path' => 'photo.jpg'],
    ]);
    $drive->allows('downloadFileTo')->andReturnUsing(function (string $id, string $path): void {
        file_put_contents($path, 'img');
    });

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'ფოტოების გადმოწერა დასრულდა'))
    );

    (new DrivePhotosDownloadJob(['from' => 1, 'to' => 3]))->handle($stateRepo, $drive, $telegram);

    @unlink(Cache::get('download_photos_ready_path:1-3'));
});

test('photos: does not create ZIP when no images found', function () {
    Cache::put('download_photos_running:1-3', true, 60);

    runPhotosJob(
        folderIndex: makePhotosFolderIndex(['folder1' => '1']),
        childFolders: ['folder1' => ['ფოტოები' => 'photos1']],
        allFiles: ['photos1' => []],
        actRange: ['from' => 1, 'to' => 3],
    );

    expect(Cache::get('download_photos_running:1-3'))->toBeNull();
    expect(Cache::has('download_photos_ready_path:1-3'))->toBeFalse();
});
