<?php

declare(strict_types=1);

use App\Services\AirtableService;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Cache;

function folderControllerAirtable(array $fields = []): AirtableService
{
    $airtable = Mockery::mock(AirtableService::class);
    $airtable->allows('getAct')->andReturn(['fields' => $fields]);
    $airtable->allows('updateActDriveFolder');
    $airtable->allows('normalizeActNumber')->andReturnArg(0);

    return $airtable;
}

// ── role guard ───────────────────────────────────────────────────────────────

test('viewer cannot create a Drive folder', function () {
    $this->app->instance(AirtableService::class, Mockery::mock(AirtableService::class));
    $this->app->instance(GoogleDriveService::class, Mockery::mock(GoogleDriveService::class));

    $this->withSession(['vigil_role' => 'viewer'])
        ->post('/dashboard/acts/recABC123/folder')
        ->assertStatus(403);
});

test('uploader cannot create a Drive folder', function () {
    $this->app->instance(AirtableService::class, Mockery::mock(AirtableService::class));
    $this->app->instance(GoogleDriveService::class, Mockery::mock(GoogleDriveService::class));

    $this->withSession(['vigil_role' => 'uploader'])
        ->post('/dashboard/acts/recABC123/folder')
        ->assertStatus(403);
});

test('unauthenticated user is redirected to login', function () {
    $this->post('/dashboard/acts/recABC123/folder')
        ->assertRedirect('/dashboard/login');
});

// ── happy path ───────────────────────────────────────────────────────────────

test('manager can create a Drive folder for an ACT without one', function () {
    $airtable = Mockery::mock(AirtableService::class);
    $airtable->expects('getAct')->with('recABC123')->andReturn([
        'fields' => ['🔖 აქტის ნომერი' => '042'],
    ]);
    $airtable->expects('updateActDriveFolder')->once();

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->expects('ensureActFolders')->once()->andReturn('https://drive.google.com/drive/folders/x');

    $this->app->instance(AirtableService::class, $airtable);
    $this->app->instance(GoogleDriveService::class, $drive);

    Cache::shouldReceive('forget')->once();

    $this->withSession(['vigil_role' => 'manager'])
        ->post('/dashboard/acts/recABC123/folder')
        ->assertRedirect();
});

test('manager sees info message when ACT already has a folder', function () {
    $airtable = Mockery::mock(AirtableService::class);
    $airtable->expects('getAct')->andReturn([
        'fields' => [
            '🔖 აქტის ნომერი' => '099',
            '💼 საქაღალდე' => 'https://drive.google.com/drive/folders/existing',
        ],
    ]);
    $airtable->shouldNotReceive('updateActDriveFolder');

    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldNotReceive('ensureActFolders');

    $this->app->instance(AirtableService::class, $airtable);
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->withSession(['vigil_role' => 'manager'])
        ->post('/dashboard/acts/recABC099/folder')
        ->assertRedirect()
        ->assertSessionHas('status');
});
