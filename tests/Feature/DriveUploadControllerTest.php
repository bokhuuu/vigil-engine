<?php

declare(strict_types=1);

use App\Services\GoogleDriveService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config([
        'vigil.dashboard.user' => 'vigil',
        'vigil.dashboard.password' => 'test-password',
        'vigil.dashboard.uploader_user' => 'colliers',
        'vigil.dashboard.uploader_password' => 'upload-password',
        'vigil.dashboard.cache_minutes' => 5,
    ]);
});

// ── helpers ───────────────────────────────────────────────────────────────────

function managerAuth(): array
{
    return ['vigil_role' => 'manager'];
}

function uploaderAuth(): array
{
    return ['vigil_role' => 'uploader'];
}

function fakeActs(): array
{
    return [
        'records' => [
            [
                'id' => 'rec001',
                'fields' => [
                    '🔖 აქტის ნომერი' => '10-1',
                    '💼 საქაღალდე' => 'https://drive.google.com/drive/folders/ACT_FOLDER_ID',
                    '🏠 შენობა/ნაგებობები' => false,
                ],
            ],
            [
                'id' => 'rec002',
                'fields' => [
                    '🔖 აქტის ნომერი' => '10-2',
                    // no folder — should be filtered out
                ],
            ],
        ],
    ];
}

// ── POST /upload ──────────────────────────────────────────────────────────────

test('upload endpoint requires authentication', function () {
    $this->postJson('/upload')->assertRedirect(route('acts.login'));
});

test('upload endpoint returns 404 for unknown act', function () {
    Cache::flush();
    Http::fake(['api.airtable.com/*' => Http::response(fakeActs(), 200)]);

    $this->withSession(uploaderAuth())
        ->post('/upload', [
            'act_id' => 'recNOTEXIST',
            'folder' => 'ფოტოები',
            'files' => [UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')],
        ])
        ->assertStatus(404);
});

test('upload endpoint returns 422 when act has no Drive folder', function () {
    Cache::flush();
    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [
                [
                    'id' => 'recNOFOLDER',
                    'fields' => ['🔖 აქტის ნომერი' => '5-1'],
                ],
            ],
        ], 200),
    ]);

    $this->withSession(uploaderAuth())
        ->post('/upload', [
            'act_id' => 'recNOFOLDER',
            'folder' => 'ფოტოები',
            'files' => [UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')],
        ])
        ->assertStatus(422);
});

test('upload endpoint calls Drive service and returns uploaded filenames', function () {
    Cache::flush();
    Http::fake(['api.airtable.com/*' => Http::response(fakeActs(), 200)]);

    $driveMock = Mockery::mock(GoogleDriveService::class);
    $driveMock->shouldReceive('findOrCreateSubfolder')
        ->once()
        ->with('https://drive.google.com/drive/folders/ACT_FOLDER_ID', 'ფოტოები')
        ->andReturn('SUBFOLDER_ID');
    $driveMock->shouldReceive('uploadFile')
        ->once()
        ->with('SUBFOLDER_ID', 'photo.jpg', Mockery::any(), Mockery::any());

    app()->instance(GoogleDriveService::class, $driveMock);

    $response = $this->withSession(uploaderAuth())
        ->post('/upload', [
            'act_id' => 'rec001',
            'folder' => 'ფოტოები',
            'files' => [UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')],
        ])
        ->assertStatus(200);

    expect($response->json('uploaded'))->toBe(['photo.jpg']);
});

test('upload endpoint accepts manager credentials too', function () {
    Cache::flush();
    Http::fake(['api.airtable.com/*' => Http::response(fakeActs(), 200)]);

    $driveMock = Mockery::mock(GoogleDriveService::class);
    $driveMock->shouldReceive('findOrCreateSubfolder')->once()->andReturn('SUBFOLDER_ID');
    $driveMock->shouldReceive('uploadFile')->once();

    app()->instance(GoogleDriveService::class, $driveMock);

    $this->withSession(managerAuth())
        ->post('/upload', [
            'act_id' => 'rec001',
            'folder' => 'ფოტოები',
            'files' => [UploadedFile::fake()->create('doc.pdf', 200, 'application/pdf')],
        ])
        ->assertStatus(200);
});
