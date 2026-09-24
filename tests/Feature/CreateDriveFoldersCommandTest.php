<?php

declare(strict_types=1);

use App\Services\AirtableService;
use App\Services\GoogleDriveService;

test('create-drive-folders skips when no acts without folder', function () {
    $airtable = Mockery::mock(AirtableService::class);
    $airtable->shouldReceive('getActsWithoutDriveFolder')
        ->with(null)
        ->once()
        ->andReturn([]);
    $airtable->shouldReceive('getRecentlyChangedActsWithFolder')->andReturn([]);

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldNotReceive('ensureActFolders');

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
    $airtable->shouldReceive('getRecentlyChangedActsWithFolder')->andReturn([]);

    $airtable->shouldReceive('updateActDriveFolder')
        ->twice();

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('ensureActFolders')
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
    $airtable->shouldReceive('getRecentlyChangedActsWithFolder')->andReturn([]);

    $airtable->shouldNotReceive('updateActDriveFolder');

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('ensureActFolders')
        ->once()
        ->andThrow(new Exception('Drive API error'));

    $this->app->instance(AirtableService::class, $airtable);
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->artisan('vigil:create-drive-folders')
        ->expectsOutput('Done. Success: 0, Failed: 1')
        ->assertExitCode(0);
});

test('create-drive-folders syncs recently changed ACTs using their saved folder', function () {
    $airtable = Mockery::mock(AirtableService::class);
    $airtable->shouldReceive('getActsWithoutDriveFolder')->andReturn([]);
    $airtable->shouldReceive('getRecentlyChangedActsWithFolder')
        ->with(30)
        ->once()
        ->andReturn([
            ['id' => 'rec590', 'fields' => [
                '🔖 აქტის ნომერი' => '590',
                '💼 საქაღალდე' => 'https://drive.google.com/drive/folders/f590',
                '🏠 შენობა/ნაგებობები' => true,
            ]],
        ]);
    $airtable->shouldNotReceive('updateActDriveFolder'); // URL already saved

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('ensureActFolders')
        ->with('590', Mockery::type('array'), 'https://drive.google.com/drive/folders/f590')
        ->once()
        ->andReturn('https://drive.google.com/drive/folders/f590');

    $this->app->instance(AirtableService::class, $airtable);
    $this->app->instance(GoogleDriveService::class, $drive);

    config(['vigil.drive.sync_window_minutes' => 30]);

    $this->artisan('vigil:create-drive-folders')
        ->expectsOutput('Synced: 1, Failed: 0 (changed in last 30 min)')
        ->assertExitCode(0);
});

test('create-drive-folders sync failure does not stop other ACTs', function () {
    $airtable = Mockery::mock(AirtableService::class);
    $airtable->shouldReceive('getActsWithoutDriveFolder')->andReturn([]);
    $airtable->shouldReceive('getRecentlyChangedActsWithFolder')->andReturn([
        ['id' => 'r1', 'fields' => ['🔖 აქტის ნომერი' => '1', '💼 საქაღალდე' => 'https://drive.google.com/drive/folders/a']],
        ['id' => 'r2', 'fields' => ['🔖 აქტის ნომერი' => '2', '💼 საქაღალდე' => 'https://drive.google.com/drive/folders/b']],
    ]);

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('ensureActFolders')
        ->with('1', Mockery::any(), Mockery::any())
        ->andThrow(new Exception('Drive API error'));
    $drive->shouldReceive('ensureActFolders')
        ->with('2', Mockery::any(), Mockery::any())
        ->once()
        ->andReturn('https://drive.google.com/drive/folders/b');

    $this->app->instance(AirtableService::class, $airtable);
    $this->app->instance(GoogleDriveService::class, $drive);

    config(['vigil.drive.sync_window_minutes' => 30]);

    $this->artisan('vigil:create-drive-folders')
        ->expectsOutput('Synced: 1, Failed: 1 (changed in last 30 min)')
        ->assertExitCode(0);
});
