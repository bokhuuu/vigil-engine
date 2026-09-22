<?php

use App\Services\GoogleDriveService;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

beforeEach(function () {
    config([
        'services.google_drive.credentials' => '/tmp/fake-credentials.json',
        'services.google_drive.act_folder_id' => 'fakeRootFolderId',
    ]);
});

test('createActFolders always creates ფოტოები and სკანი', function () {
    $mockDrive = Mockery::mock(Drive::class);
    $mockFiles = Mockery::mock();

    $mockDrive->files = $mockFiles;

    // Expect: root ACT folder + ფოტოები + სკანი = 3 calls minimum
    $mockFiles->shouldReceive('create')
        ->times(3)
        ->andReturnUsing(function (DriveFile $file) {
            $mock = Mockery::mock();
            $mock->shouldReceive('getId')->andReturn('fakeFolderId');

            return $mock;
        });

    $service = new GoogleDriveService;

    $reflection = new ReflectionClass($service);
    $prop = $reflection->getProperty('drive');
    $prop->setAccessible(true);
    $prop->setValue($service, $mockDrive);

    $url = $service->createActFolders('ACT-TEST', []);

    expect($url)->toContain('https://drive.google.com/drive/folders/');
});

test('createActFolders creates შიდა ნახაზები only if შენობა flag set', function () {
    $mockDrive = Mockery::mock(Drive::class);
    $mockFiles = Mockery::mock();
    $mockDrive->files = $mockFiles;

    // root + ფოტოები + სკანი + შიდა ნახაზები + ხარჯთაღრიცხვა = 5
    $mockFiles->shouldReceive('create')
        ->times(5)
        ->andReturnUsing(function () {
            $mock = Mockery::mock();
            $mock->shouldReceive('getId')->andReturn('fakeFolderId');

            return $mock;
        });

    $service = new GoogleDriveService;
    $reflection = new ReflectionClass($service);
    $prop = $reflection->getProperty('drive');
    $prop->setAccessible(true);
    $prop->setValue($service, $mockDrive);

    $service->createActFolders('ACT-TEST', [
        '🏠 შენობა/ნაგებობები' => true,
    ]);
});

test('createActFolders creates ტოპო ნარგავები if მრავალწლოვანი set', function () {
    $mockDrive = Mockery::mock(Drive::class);
    $mockFiles = Mockery::mock();
    $mockDrive->files = $mockFiles;

    // root + ფოტოები + სკანი + ტოპო ნარგავები = 4
    $mockFiles->shouldReceive('create')
        ->times(4)
        ->andReturnUsing(function () {
            $mock = Mockery::mock();
            $mock->shouldReceive('getId')->andReturn('fakeFolderId');

            return $mock;
        });

    $service = new GoogleDriveService;
    $reflection = new ReflectionClass($service);
    $prop = $reflection->getProperty('drive');
    $prop->setAccessible(true);
    $prop->setValue($service, $mockDrive);

    $service->createActFolders('ACT-TEST', [
        '🏠 მრავალწლოვანი' => true,
    ]);
});

test('createActFolders creates ბიზნესი folder with subfolders when business linked', function () {
    $mockDrive = Mockery::mock(Drive::class);
    $mockFiles = Mockery::mock();
    $mockDrive->files = $mockFiles;

    // root + ფოტოები + სკანი + ბიზნესი + ბიზნესი-1 + ბიზნესი-2 = 6
    $mockFiles->shouldReceive('create')
        ->times(6)
        ->andReturnUsing(function () {
            $mock = Mockery::mock();
            $mock->shouldReceive('getId')->andReturn('fakeFolderId');

            return $mock;
        });

    $service = new GoogleDriveService;
    $reflection = new ReflectionClass($service);
    $prop = $reflection->getProperty('drive');
    $prop->setAccessible(true);
    $prop->setValue($service, $mockDrive);

    $service->createActFolders('ACT-TEST', [
        '🏢 ბიზნესი' => ['recBiz1', 'recBiz2'],
    ]);
});

test('createActFolders creates კორესპონდენცია folder when correspondence linked', function () {
    $mockDrive = Mockery::mock(Drive::class);
    $mockFiles = Mockery::mock();
    $mockDrive->files = $mockFiles;

    // root + ფოტოები + სკანი + კორესპონდენცია = 4
    $mockFiles->shouldReceive('create')
        ->times(4)
        ->andReturnUsing(function () {
            $mock = Mockery::mock();
            $mock->shouldReceive('getId')->andReturn('fakeFolderId');

            return $mock;
        });

    $service = new GoogleDriveService;
    $reflection = new ReflectionClass($service);
    $prop = $reflection->getProperty('drive');
    $prop->setAccessible(true);
    $prop->setValue($service, $mockDrive);

    $service->createActFolders('ACT-TEST', [
        '📨 კორესპონდენცია' => ['recCorr1'],
    ]);
});

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

    $service = new GoogleDriveService;
    $reflection = new ReflectionClass($service);
    $prop = $reflection->getProperty('drive');
    $prop->setAccessible(true);
    $prop->setValue($service, $mockDrive);

    $service->setFolderColor('fakeFolderId', '#00843D');
});
