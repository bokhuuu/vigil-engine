<?php

use App\Services\AirtableService;
use App\Services\GoogleDriveService;

test('create-drive-folders skips when no acts without folder', function () {
    $airtable = Mockery::mock(AirtableService::class);
    $airtable->shouldReceive('getActsWithoutDriveFolder')
        ->with(null)
        ->once()
        ->andReturn([]);

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldNotReceive('createActFolders');

    $this->app->instance(AirtableService::class, $airtable);
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->artisan('vigil:create-drive-folders')
        ->expectsOutput('No ACTs without Drive folders found.')
        ->assertExitCode(0);
});

test('create-drive-folders processes acts and writes folder url', function () {
    $airtable = Mockery::mock(AirtableService::class);
    $airtable->shouldReceive('getActsWithoutDriveFolder')
        ->with(2)
        ->once()
        ->andReturn([
            ['id' => 'rec001', 'fields' => ['🔖 აქტის ნომერი' => '001']],
            ['id' => 'rec002', 'fields' => ['🔖 აქტის ნომერი' => '002']],
        ]);

    $airtable->shouldReceive('updateActDriveFolder')
        ->twice();

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('createActFolders')
        ->twice()
        ->andReturn('https://drive.google.com/drive/folders/abc123');

    $this->app->instance(AirtableService::class, $airtable);
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->artisan('vigil:create-drive-folders', ['--limit' => 2])
        ->expectsOutput('Processing 2 ACTs...')
        ->expectsOutput('Done. Success: 2, Failed: 0')
        ->assertExitCode(0);
});

test('create-drive-folders counts failed acts correctly', function () {
    $airtable = Mockery::mock(AirtableService::class);
    $airtable->shouldReceive('getActsWithoutDriveFolder')
        ->with(null)
        ->once()
        ->andReturn([
            ['id' => 'rec001', 'fields' => ['🔖 აქტის ნომერი' => '001']],
        ]);

    $airtable->shouldNotReceive('updateActDriveFolder');

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('createActFolders')
        ->once()
        ->andThrow(new Exception('Drive API error'));

    $this->app->instance(AirtableService::class, $airtable);
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->artisan('vigil:create-drive-folders')
        ->expectsOutput('Done. Success: 0, Failed: 1')
        ->assertExitCode(0);
});
