<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

test('webhook rejects request with invalid secret', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response([], 200),
    ]);

    $response = $this->postJson('/webhook/airtable', [], [
        'X-Airtable-Hmac-SHA256' => 'wrong-secret',
    ]);

    $response->assertStatus(401);
});

test('webhook accepts valid request and schedules reminder for inspector team', function () {
    Cache::flush();

    config(['vigil.webhook.secret' => 'test-secret']);

    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            // getUpdateRecord
            ->push([
                'id' => 'recTest123',
                'fields' => [
                    '👥 გუნდები' => ['recTeam1'],
                ],
            ], 200)
            // getTeamRecord
            ->push([
                'id' => 'recTeam1',
                'fields' => [
                    '👤 სახელი' => 'ჩადუ & გივიკო',
                ],
            ], 200),
    ]);

    $response = $this->postJson(
        '/webhook/airtable?secret=test-secret',
        ['record_id' => 'recTest123']
    );

    $response->assertStatus(200);

    $cacheKey = 'update_reminder_'.date('Y-m-d').'_'.md5('ჩადუ & გივიკო');
    expect(Cache::has($cacheKey))->toBeTrue();
});

test('webhook ignores non-inspector teams', function () {
    Cache::flush();

    config(['vigil.webhook.secret' => 'test-secret']);

    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push([
                'id' => 'recTest456',
                'fields' => [
                    '👥 გუნდები' => ['recTeam2'],
                ],
            ], 200)
            ->push([
                'id' => 'recTeam2',
                'fields' => [
                    '👤 სახელი' => 'ამზომველები',
                ],
            ], 200),
    ]);

    $response = $this->postJson(
        '/webhook/airtable?secret=test-secret',
        ['record_id' => 'recTest456']
    );

    $response->assertStatus(200);

    $cacheKey = 'update_reminder_'.date('Y-m-d').'_'.md5('ამზომველები');
    expect(Cache::has($cacheKey))->toBeFalse();
});

test('webhook does not schedule twice for same team same day', function () {
    Cache::flush();

    config(['vigil.webhook.secret' => 'test-secret']);

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'id' => 'recTest789',
            'fields' => [
                '👥 გუნდები' => ['recTeam3'],
                '👤 სახელი' => 'გიო & სანდრიკა',
            ],
        ], 200),
    ]);

    $payload = ['record_id' => 'recTest789'];

    // First submission
    $this->postJson('/webhook/airtable?secret=test-secret', $payload);

    $cacheKey = 'update_reminder_'.date('Y-m-d').'_'.md5('გიო & სანდრიკა');
    $firstTime = Cache::get($cacheKey)['scheduled_at'];

    // Second submission
    $this->postJson('/webhook/airtable?secret=test-secret', $payload);

    expect(Cache::get($cacheKey)['scheduled_at'])->toBe($firstTime);
});
