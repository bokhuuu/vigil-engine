<?php

declare(strict_types=1);

use App\Services\ActRequirements;
use App\Services\GoogleDriveService;
use Google\Service\Drive;

beforeEach(function () {
    config([
        'services.google_drive.credentials' => '/tmp/fake-credentials.json',
        'services.google_drive.act_folder_id' => 'fakeRootFolderId',
    ]);
});

// Folder creation logic (ensureActFolders) is tested in tests/Unit/GoogleDriveServiceTest.php

test('setFolderColor calls Drive API update with correct color', function () {
    $mockDrive = Mockery::mock(Drive::class);
    $mockFiles = Mockery::mock();
    $mockDrive->files = $mockFiles;

    $mockFiles->shouldReceive('update')
        ->once()
        ->andReturnUsing(function () {
            $mock = Mockery::mock();
            $mock->shouldReceive('getId')->andReturn('fakeFolderId');

            return $mock;
        });

    $service = new GoogleDriveService(new ActRequirements);
    $reflection = new ReflectionClass($service);
    $prop = $reflection->getProperty('drive');
    $prop->setAccessible(true);
    $prop->setValue($service, $mockDrive);

    $service->setFolderColor('fakeFolderId', '#00843D');
});
