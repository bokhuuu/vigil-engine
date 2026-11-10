<?php

declare(strict_types=1);

use App\Jobs\DrivePhotosUploadJob;
use App\Repositories\DriveWatcherStateRepository;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Tests\TestCase;

uses(TestCase::class);

// ── helpers ────────────────────────────────────────────────────────────────

function makePhotosUploadZip(array $entries): string
{
    $tmpZip = tempnam(sys_get_temp_dir(), 'vigil_photos_test_').'.zip';
    $zip = new ZipArchive;
    $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($entries as $name => $content) {
        $zip->addFromString($name, $content);
    }

    $zip->close();

    return $tmpZip;
}

function makePhotosUploadFolderIndex(array $entries = []): array
{
    $index = [];
    foreach ($entries as $id => $number) {
        $index[$id] = ['act_number' => $number, 'act_record_id' => 'rec_'.$number];
    }

    return $index;
}

// ── tests ──────────────────────────────────────────────────────────────────

test('upload photos: aborts when folder_index is empty', function () {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn([]);

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturn([]);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'folder_index ცარიელია'))
    );

    (new DrivePhotosUploadJob)->handle($stateRepo, $drive, $telegram);
});

test('upload photos: aborts when no ZIP in staging', function () {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makePhotosUploadFolderIndex(['folder1' => '1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturn([]);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'ZIP ფაილი არ არის'))
    );

    (new DrivePhotosUploadJob)->handle($stateRepo, $drive, $telegram);
});

test('upload photos: warns about non-ZIP files in staging', function () {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makePhotosUploadFolderIndex(['folder1' => '1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturn([
        ['id' => 'f1', 'name' => 'photo.jpg', 'mimeType' => 'image/jpeg'],
    ]);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'მოულოდნელი ფაილი'))
    );
    $telegram->allows('sendToGroup')->withAnyArgs();

    (new DrivePhotosUploadJob)->handle($stateRepo, $drive, $telegram);
});

test('upload photos: extracts ACT number and uploads to ფოტოები subfolder', function () {
    $zipPath = makePhotosUploadZip(['ფოტო_505.jpg' => 'JFIF']);

    $stagingFolderId = config('services.google_drive.staging.photos');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makePhotosUploadFolderIndex(['folder505' => '505'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        function (string $id) use ($stagingFolderId, $zipPath) {
            if ($id === $stagingFolderId) {
                return [['id' => 'zip1', 'name' => 'photos.zip', 'mimeType' => 'application/zip', '_zip_path' => $zipPath]];
            }

            return []; // empty ფოტოები subfolder
        }
    );
    $drive->allows('listChildFolders')->andReturn(['ფოტოები' => 'photos1']);
    $drive->allows('downloadFileTo')->andReturnUsing(
        function (string $id, string $tmpPath) use ($zipPath): void {
            copy($zipPath, $tmpPath);
        }
    );
    $drive->expects('uploadFile')->once()->with(
        'photos1',
        'ფოტო_505.jpg',
        Mockery::any(),
        'image/jpeg'
    )->andReturn('new_file_id');
    $drive->allows('trashFile')->andReturn(null);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'ატვირთულია: 1'))
    );

    (new DrivePhotosUploadJob)->handle($stateRepo, $drive, $telegram);

    @unlink($zipPath);
});

test('upload photos: strips parenthesised copy markers before extracting ACT number', function () {
    // e.g. "ფოტო_505 (2).jpg" → cleaned "ფოტო_505 .jpg" → ACT 505
    $zipPath = makePhotosUploadZip(['ფოტო_505 (2).jpg' => 'JFIF']);

    $stagingFolderId = config('services.google_drive.staging.photos');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makePhotosUploadFolderIndex(['folder505' => '505'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        function (string $id) use ($stagingFolderId, $zipPath) {
            if ($id === $stagingFolderId) {
                return [['id' => 'zip1', 'name' => 'photos.zip', 'mimeType' => 'application/zip', '_zip_path' => $zipPath]];
            }

            return [];
        }
    );
    $drive->allows('listChildFolders')->andReturn(['ფოტოები' => 'photos1']);
    $drive->allows('downloadFileTo')->andReturnUsing(
        function (string $id, string $tmpPath) use ($zipPath): void {
            copy($zipPath, $tmpPath);
        }
    );
    $drive->expects('uploadFile')->once()->with(
        'photos1',
        'ფოტო_505 (2).jpg',
        Mockery::any(),
        'image/jpeg'
    )->andReturn('new_file_id');
    $drive->allows('trashFile')->andReturn(null);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('sendToGroup')->withAnyArgs();

    (new DrivePhotosUploadJob)->handle($stateRepo, $drive, $telegram);

    @unlink($zipPath);
});

test('upload photos: skip mode skips file that already exists', function () {
    $zipPath = makePhotosUploadZip(['ფოტო_505.jpg' => 'JFIF']);

    $stagingFolderId = config('services.google_drive.staging.photos');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makePhotosUploadFolderIndex(['folder505' => '505'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        function (string $id) use ($stagingFolderId, $zipPath) {
            if ($id === $stagingFolderId) {
                return [['id' => 'zip1', 'name' => 'photos.zip', 'mimeType' => 'application/zip', '_zip_path' => $zipPath]];
            }

            // ფოტოები subfolder already has the file
            return [['id' => 'existing', 'name' => 'ფოტო_505.jpg', 'mimeType' => 'image/jpeg']];
        }
    );
    $drive->allows('listChildFolders')->andReturn(['ფოტოები' => 'photos1']);
    $drive->allows('downloadFileTo')->andReturnUsing(
        function (string $id, string $tmpPath) use ($zipPath): void {
            copy($zipPath, $tmpPath);
        }
    );
    $drive->expects('uploadFile')->never();
    $drive->allows('trashFile')->andReturn(null);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'უკვე არსებობს: 1'))
    );

    (new DrivePhotosUploadJob)->handle($stateRepo, $drive, $telegram);

    @unlink($zipPath);
});

test('upload photos: skips file when ACT number not in folder_index', function () {
    $zipPath = makePhotosUploadZip(['ფოტო_999.jpg' => 'JFIF']);

    $stagingFolderId = config('services.google_drive.staging.photos');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makePhotosUploadFolderIndex(['folder1' => '1']) // only ACT 1, not 999
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        function (string $id) use ($stagingFolderId, $zipPath) {
            if ($id === $stagingFolderId) {
                return [['id' => 'zip1', 'name' => 'photos.zip', 'mimeType' => 'application/zip', '_zip_path' => $zipPath]];
            }

            return [];
        }
    );
    $drive->allows('downloadFileTo')->andReturnUsing(
        function (string $id, string $tmpPath) use ($zipPath): void {
            copy($zipPath, $tmpPath);
        }
    );
    $drive->expects('uploadFile')->never();
    $drive->allows('trashFile')->andReturn(null);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'ACT ვერ მოიძებნა: 1'))
    );

    (new DrivePhotosUploadJob)->handle($stateRepo, $drive, $telegram);

    @unlink($zipPath);
});

test('upload photos: skips non-image entries in ZIP silently', function () {
    $zipPath = makePhotosUploadZip([
        'ფოტო_505.jpg' => 'JFIF',
        'notes.txt' => 'ignore me',
        'scan.pdf' => '%PDF',
    ]);

    $stagingFolderId = config('services.google_drive.staging.photos');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makePhotosUploadFolderIndex(['folder505' => '505'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        function (string $id) use ($stagingFolderId, $zipPath) {
            if ($id === $stagingFolderId) {
                return [['id' => 'zip1', 'name' => 'photos.zip', 'mimeType' => 'application/zip', '_zip_path' => $zipPath]];
            }

            return [];
        }
    );
    $drive->allows('listChildFolders')->andReturn(['ფოტოები' => 'photos1']);
    $drive->allows('downloadFileTo')->andReturnUsing(
        function (string $id, string $tmpPath) use ($zipPath): void {
            copy($zipPath, $tmpPath);
        }
    );
    // Only one upload — the jpg, not txt or pdf
    $drive->expects('uploadFile')->once()->andReturn('new_file_id');
    $drive->allows('trashFile')->andReturn(null);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('sendToGroup')->withAnyArgs();

    (new DrivePhotosUploadJob)->handle($stateRepo, $drive, $telegram);

    @unlink($zipPath);
});
