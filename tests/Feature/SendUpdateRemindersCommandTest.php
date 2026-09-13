<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

test('send update reminders skips teams with no pending cache', function () {
    Cache::flush();

    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:send-update-reminders')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('send update reminders skips team if 15 minutes not passed', function () {
    Cache::flush();

    $teamName = 'ჩადუ & გივიკო';
    $cacheKey = 'update_reminder_'.date('Y-m-d').'_'.md5($teamName);

    Cache::put($cacheKey, [
        'team' => $teamName,
        'scheduled_at' => now()->toIso8601String(),
    ], now()->endOfDay());

    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:send-update-reminders')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('send update reminders sends telegram after 15 minutes', function () {
    Cache::flush();

    $teamName = 'ჩადუ & გივიკო';
    $cacheKey = 'update_reminder_'.date('Y-m-d').'_'.md5($teamName);

    Cache::put($cacheKey, [
        'team' => $teamName,
        'scheduled_at' => now()->subMinutes(16)->toIso8601String(),
    ], now()->endOfDay());

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [
                ['id' => 'recT1', 'fields' => [
                    '👤 სახელი' => 'ჩადუ & გივიკო',
                    '🏷️ როლი' => 'ინსპექტორი',
                    '✈️ Telegram' => '1606755061',
                    '✈️ Telegram 2' => '5399299383',
                ]],
            ],
        ], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:send-update-reminders')
        ->assertSuccessful()
        ->expectsOutputToContain('Sent update reminder to ჩადუ & გივიკო');

    Http::assertSent(
        fn ($request) => str_contains($request->url(), 'api.telegram.org')
    );

    expect(Cache::has($cacheKey))->toBeFalse();
});
