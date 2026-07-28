<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

test('backup command runs successfully', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:backup')
        ->assertSuccessful();
});

test('backup command creates json file in storage', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:backup')
        ->assertSuccessful();

    $filename = 'backup-'.now()->format('Y-m-d').'.json';
    $path = storage_path("backups/{$filename}");

    expect(File::exists($path))->toBeTrue();

    // Clean up
    File::delete($path);
});

test('backup command sends telegram confirmation', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:backup')
        ->assertSuccessful();

    Http::assertSent(
        fn ($request) => str_contains($request->url(), 'api.telegram.org') &&
            str_contains($request['text'], 'Backup')
    );
});

test('backup command fetches all 5 tables', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:backup')
        ->assertSuccessful();

    Http::assertSentCount(7); // 6 Airtable GETs + 1 Telegram
});
