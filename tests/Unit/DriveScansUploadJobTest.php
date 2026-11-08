<?php

declare(strict_types=1);

use App\Jobs\DriveScansUploadJob;
use App\Repositories\DriveWatcherStateRepository;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Tests\TestCase;

uses(TestCase::class);

// ── helpers ────────────────────────────────────────────────────────────────

function makeUploadFolderIndex(array $entries = []): array
{
    $index = [];
    foreach ($entries as $id => $number) {
        $index[$id] = ['act_number' => $number, 'act_record_id' => 'rec_'.$number];
    }

    return $index;
}

/**
 * Build a real ZIP in a temp file and return its path.
 * $entries = ['filename.pdf' => 'content', ...]
 */
function makeTestZip(array $entries): string
{
    $tmpZip = tempnam(sys_get_temp_dir(), 'vigil_test_zip_').'.zip';
    $zip = new ZipArchive;
    $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($entries as $name => $content) {
        $zip->addFromString($name, $content);
    }

    $zip->close();

    return $tmpZip;
}

function runUploadJob(
    array $folderIndex,
    array $stagingFiles = [],
    array $childFolders = [],   // actFolderId => ['სკანი' => subFolderId]
    array $existingFiles = [],  // subFolderId => [['id','name','mimeType']]
    string $mode = 'skip',
): void {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn($folderIndex);

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        fn (string $id) => $stagingFiles[$id] ?? ($existingFiles[$id] ?? [])
    );
    $drive->allows('listChildFolders')->andReturnUsing(
        fn (string $id) => $childFolders[$id] ?? []
    );
    $drive->allows('findOrCreateFolder')->andReturnUsing(
        fn (string $name, string $parentId) => 'created_'.$name.'_'.$parentId
    );
    $drive->allows('downloadFileTo')->andReturnUsing(
        function (string $fileId, string $tmpPath) use ($stagingFiles): void {
            // Find the zip path stored in staging files
            foreach ($stagingFiles as $files) {
                foreach ($files as $f) {
                    if ($f['id'] === $fileId && isset($f['_zip_path'])) {
                        copy($f['_zip_path'], $tmpPath);

                        return;
                    }
                }
            }
            file_put_contents($tmpPath, '');
        }
    );
    $drive->allows('uploadFile')->andReturn('new_file_id');
    $drive->allows('trashFolderChildren')->andReturn(null);
    $drive->allows('trashFile')->andReturn(null);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('sendToGroup')->withAnyArgs();

    (new DriveScansUploadJob($mode))->handle($stateRepo, $drive, $telegram);
}

// ── tests ──────────────────────────────────────────────────────────────────

test('upload scans: aborts when folder_index is empty', function () {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn([]);

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturn([]);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'folder_index ცარიელია'))
    );

    (new DriveScansUploadJob)->handle($stateRepo, $drive, $telegram);
});

test('upload scans: aborts when no ZIP in staging', function () {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makeUploadFolderIndex(['folder1' => '1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturn([]);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'ZIP ფაილი არ არის'))
    );

    (new DriveScansUploadJob)->handle($stateRepo, $drive, $telegram);
});

test('upload scans: warns about non-ZIP files in staging', function () {
    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makeUploadFolderIndex(['folder1' => '1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturn([
        ['id' => 'f1', 'name' => 'scan.pdf', 'mimeType' => 'application/pdf'],
    ]);

    $telegram = Mockery::mock(TelegramService::class);
    // First call: warning about the non-ZIP file
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'მოულოდნელი ფაილი'))
    );
    // Second call: abort because no ZIPs remain
    $telegram->allows('sendToGroup')->withAnyArgs();

    (new DriveScansUploadJob)->handle($stateRepo, $drive, $telegram);
});

test('upload scans: uploads PDF to correct ACT სკანი subfolder', function () {
    $zipPath = makeTestZip(['სკანი_1.pdf' => '%PDF content']);

    $stagingFolderId = config('services.google_drive.staging.scans');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makeUploadFolderIndex(['folder1' => '1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        function (string $id) use ($stagingFolderId, $zipPath) {
            if ($id === $stagingFolderId) {
                return [['id' => 'zip1', 'name' => 'scans.zip', 'mimeType' => 'application/zip', '_zip_path' => $zipPath]];
            }

            return []; // empty სკანი subfolder
        }
    );
    $drive->allows('listChildFolders')->andReturn(['სკანი' => 'skani1']);
    $drive->allows('downloadFileTo')->andReturnUsing(
        function (string $id, string $tmpPath) use ($zipPath): void {
            copy($zipPath, $tmpPath);
        }
    );
    $drive->expects('uploadFile')->once()->with(
        'skani1',
        'სკანი_1.pdf',
        Mockery::any(),
        'application/pdf'
    )->andReturn('new_id');

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToGroup')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'ატვირთულია: 1'))
    );

    (new DriveScansUploadJob('skip'))->handle($stateRepo, $drive, $telegram);

    @unlink($zipPath);
});

test('upload scans: skip mode skips file that already exists', function () {
    $zipPath = makeTestZip(['სკანი_1.pdf' => '%PDF content']);

    $stagingFolderId = config('services.google_drive.staging.scans');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makeUploadFolderIndex(['folder1' => '1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        function (string $id) use ($stagingFolderId, $zipPath) {
            if ($id === $stagingFolderId) {
                return [['id' => 'zip1', 'name' => 'scans.zip', 'mimeType' => 'application/zip', '_zip_path' => $zipPath]];
            }

            // სკანი subfolder already has the file
            return [['id' => 'existing', 'name' => 'სკანი_1.pdf', 'mimeType' => 'application/pdf']];
        }
    );
    $drive->allows('listChildFolders')->andReturn(['სკანი' => 'skani1']);
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

    (new DriveScansUploadJob('skip'))->handle($stateRepo, $drive, $telegram);

    @unlink($zipPath);
});

test('upload scans: replace mode trashes existing files before upload', function () {
    $zipPath = makeTestZip(['სკანი_1.pdf' => '%PDF content']);

    $stagingFolderId = config('services.google_drive.staging.scans');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makeUploadFolderIndex(['folder1' => '1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        function (string $id) use ($stagingFolderId, $zipPath) {
            if ($id === $stagingFolderId) {
                return [['id' => 'zip1', 'name' => 'scans.zip', 'mimeType' => 'application/zip', '_zip_path' => $zipPath]];
            }

            return [];
        }
    );
    $drive->allows('listChildFolders')->andReturn(['სკანი' => 'skani1']);
    $drive->allows('downloadFileTo')->andReturnUsing(
        function (string $id, string $tmpPath) use ($zipPath): void {
            copy($zipPath, $tmpPath);
        }
    );
    $drive->expects('trashFolderChildren')->once()->with('skani1');
    $drive->allows('uploadFile')->andReturn('new_id');
    $drive->allows('trashFile')->andReturn(null);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('sendToGroup')->withAnyArgs();

    (new DriveScansUploadJob('replace'))->handle($stateRepo, $drive, $telegram);

    @unlink($zipPath);
});

test('upload scans: skips PDF when ACT number not in folder_index', function () {
    $zipPath = makeTestZip(['სკანი_999.pdf' => '%PDF content']);

    $stagingFolderId = config('services.google_drive.staging.scans');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makeUploadFolderIndex(['folder1' => '1']) // only ACT 1, not 999
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        function (string $id) use ($stagingFolderId, $zipPath) {
            if ($id === $stagingFolderId) {
                return [['id' => 'zip1', 'name' => 'scans.zip', 'mimeType' => 'application/zip', '_zip_path' => $zipPath]];
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

    (new DriveScansUploadJob('skip'))->handle($stateRepo, $drive, $telegram);

    @unlink($zipPath);
});

test('upload scans: skips non-PDF entries in ZIP silently', function () {
    $zipPath = makeTestZip([
        'სკანი_1.pdf' => '%PDF content',
        'readme.txt' => 'ignore me',
    ]);

    $stagingFolderId = config('services.google_drive.staging.scans');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makeUploadFolderIndex(['folder1' => '1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        function (string $id) use ($stagingFolderId, $zipPath) {
            if ($id === $stagingFolderId) {
                return [['id' => 'zip1', 'name' => 'scans.zip', 'mimeType' => 'application/zip', '_zip_path' => $zipPath]];
            }

            return [];
        }
    );
    $drive->allows('listChildFolders')->andReturn(['სკანი' => 'skani1']);
    $drive->allows('downloadFileTo')->andReturnUsing(
        function (string $id, string $tmpPath) use ($zipPath): void {
            copy($zipPath, $tmpPath);
        }
    );
    // Only one upload — the PDF, not the txt
    $drive->expects('uploadFile')->once()->andReturn('new_id');
    $drive->allows('trashFile')->andReturn(null);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('sendToGroup')->withAnyArgs();

    (new DriveScansUploadJob('skip'))->handle($stateRepo, $drive, $telegram);

    @unlink($zipPath);
});

test('upload scans: trashes staging ZIP after processing', function () {
    $zipPath = makeTestZip(['სკანი_1.pdf' => '%PDF content']);

    $stagingFolderId = config('services.google_drive.staging.scans');

    $stateRepo = Mockery::mock(DriveWatcherStateRepository::class);
    $stateRepo->allows('getFolderIndex')->andReturn(
        makeUploadFolderIndex(['folder1' => '1'])
    );

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->allows('listFilesInFolder')->andReturnUsing(
        function (string $id) use ($stagingFolderId, $zipPath) {
            if ($id === $stagingFolderId) {
                return [['id' => 'zip1', 'name' => 'scans.zip', 'mimeType' => 'application/zip', '_zip_path' => $zipPath]];
            }

            return [];
        }
    );
    $drive->allows('listChildFolders')->andReturn(['სკანი' => 'skani1']);
    $drive->allows('downloadFileTo')->andReturnUsing(
        function (string $id, string $tmpPath) use ($zipPath): void {
            copy($zipPath, $tmpPath);
        }
    );
    $drive->allows('uploadFile')->andReturn('new_id');
    $drive->expects('trashFile')->once()->with('zip1');

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->allows('sendToGroup')->withAnyArgs();

    (new DriveScansUploadJob('skip'))->handle($stateRepo, $drive, $telegram);

    @unlink($zipPath);
});
