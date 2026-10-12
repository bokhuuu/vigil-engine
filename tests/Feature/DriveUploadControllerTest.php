<?php

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

// ── helpers ──────────────────────────────────────────────────────────────────

function managerAuth(): array
{
    return ['Authorization' => 'Basic '.base64_encode('vigil:test-password')];
}

function uploaderAuth(): array
{
    return ['Authorization' => 'Basic '.base64_encode('colliers:upload-password')];
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

// ── GET /upload ───────────────────────────────────────────────────────────────

test('upload page rejects unauthenticated request', function () {
    $this->get('/upload')->assertStatus(401);
});

test('upload page rejects wrong credentials', function () {
    $this->withHeaders(['Authorization' => 'Basic '.base64_encode('colliers:wrong')])
        ->get('/upload')
        ->assertStatus(401);
});

test('upload page returns 200 for manager credentials', function () {
    Cache::flush();
    Http::fake(['api.airtable.com/*' => Http::response(fakeActs(), 200)]);

    $this->withHeaders(managerAuth())
        ->get('/upload')
        ->assertStatus(200)
        ->assertViewIs('upload');
});

test('upload page returns 200 for uploader credentials', function () {
    Cache::flush();
    Http::fake(['api.airtable.com/*' => Http::response(fakeActs(), 200)]);

    $this->withHeaders(uploaderAuth())
        ->get('/upload')
        ->assertStatus(200)
        ->assertViewIs('upload');
});

test('upload page only lists acts that have a Drive folder', function () {
    Cache::flush();
    Http::fake(['api.airtable.com/*' => Http::response(fakeActs(), 200)]);

    $this->withHeaders(uploaderAuth())
        ->get('/upload')
        ->assertStatus(200)
        ->assertViewHas('acts', fn ($acts) => $acts->count() === 1 && $acts->first()['id'] === 'rec001');
});

test('upload page passes role to view for manager', function () {
    Cache::flush();
    Http::fake(['api.airtable.com/*' => Http::response(fakeActs(), 200)]);

    $this->withHeaders(managerAuth())
        ->get('/upload')
        ->assertViewHas('role', 'manager');
});

test('upload page passes role to view for uploader', function () {
    Cache::flush();
    Http::fake(['api.airtable.com/*' => Http::response(fakeActs(), 200)]);

    $this->withHeaders(uploaderAuth())
        ->get('/upload')
        ->assertViewHas('role', 'uploader');
});

test('upload page reuses dashboard cache when available', function () {
    Cache::put('dashboard_snapshot', [
        'acts' => [
            [
                'id' => 'recCACHED',
                'fields' => [
                    '🔖 აქტის ნომერი' => '99-1',
                    '💼 საქაღალდე' => 'https://drive.google.com/drive/folders/CACHED_ID',
                ],
            ],
        ],
        'fetched_at' => now()->toDateTimeString(),
    ]);

    // Airtable must NOT be called — Http::preventStrayRequests() will throw if it is
    $this->withHeaders(uploaderAuth())
        ->get('/upload')
        ->assertStatus(200)
        ->assertViewHas('acts', fn ($acts) => $acts->first()['id'] === 'recCACHED');
});

// ── GET /upload/folders ───────────────────────────────────────────────────────

test('folders endpoint requires authentication', function () {
    $this->getJson('/upload/folders?act=rec001')->assertStatus(401);
});

test('folders endpoint returns 404 for unknown act id', function () {
    Cache::flush();
    Http::fake(['api.airtable.com/*' => Http::response(fakeActs(), 200)]);

    $this->getJson('/upload/folders?act=recNOTEXIST', uploaderAuth())
        ->assertStatus(404);
});

test('folders endpoint returns folder list for valid act', function () {
    Cache::flush();
    Http::fake(['api.airtable.com/*' => Http::response(fakeActs(), 200)]);

    $response = $this->getJson('/upload/folders?act=rec001', uploaderAuth())
        ->assertStatus(200);

    $response->assertJsonStructure(['folders' => []]);
    expect($response->json('folders'))->toBeArray()->not->toBeEmpty();
});

// ── POST /upload ──────────────────────────────────────────────────────────────

test('upload endpoint requires authentication', function () {
    $this->postJson('/upload')->assertStatus(401);
});

test('upload endpoint returns 404 for unknown act', function () {
    Cache::flush();
    Http::fake(['api.airtable.com/*' => Http::response(fakeActs(), 200)]);

    $this->withHeaders(uploaderAuth())
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

    $this->withHeaders(uploaderAuth())
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

    $response = $this->withHeaders(uploaderAuth())
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

    $this->withHeaders(managerAuth())
        ->post('/upload', [
            'act_id' => 'rec001',
            'folder' => 'ფოტოები',
            'files' => [UploadedFile::fake()->create('doc.pdf', 200, 'application/pdf')],
        ])
        ->assertStatus(200);
});
