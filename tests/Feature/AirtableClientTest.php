<?php

declare(strict_types=1);

use App\Exceptions\AirtableException;
use App\Services\AirtableClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'vigil.airtable.retry_times' => 3,
        'vigil.airtable.retry_sleep_ms' => 0,   // don't actually wait in tests
        'vigil.telegram.bot_token' => 'test-token',
        'vigil.telegram.my_chat_id' => '999',
    ]);
});

$airtableCalls = fn () => Http::recorded(
    fn ($request) => str_contains($request->url(), 'api.airtable.com')
)->count();

test('get returns records from airtable', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [
                ['id' => 'rec123', 'fields' => ['🔖 აქტის ნომერი' => 'ACT-001']],
                ['id' => 'rec456', 'fields' => ['🔖 აქტის ნომერი' => 'ACT-002']],
            ],
        ], 200),
    ]);

    $client = new AirtableClient;
    $records = $client->get('tblqVYQxgMXwgyY6r');

    expect($records)->toHaveCount(2);
    expect($records[0]['id'])->toBe('rec123');
});

test('get handles pagination automatically', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push([
                'records' => [
                    ['id' => 'rec001', 'fields' => []],
                ],
                'offset' => 'page2offset',
            ], 200)
            ->push([
                'records' => [
                    ['id' => 'rec002', 'fields' => []],
                ],
            ], 200),
    ]);

    $client = new AirtableClient;
    $records = $client->get('tblqVYQxgMXwgyY6r');

    expect($records)->toHaveCount(2);
    expect($records[0]['id'])->toBe('rec001');
    expect($records[1]['id'])->toBe('rec002');
});

test('get throws on client error and does not retry it', function () use ($airtableCalls) {
    Http::fake([
        'api.airtable.com/*' => Http::response(['error' => 'AUTHENTICATION_REQUIRED'], 401),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    expect(fn () => (new AirtableClient)->get('tblqVYQxgMXwgyY6r'))
        ->toThrow(AirtableException::class);

    expect($airtableCalls())->toBe(1);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.telegram.org'));
});

test('get retries a 504 and then succeeds', function () use ($airtableCalls) {
    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push('gateway timeout', 504)
            ->push(['records' => [['id' => 'r1', 'fields' => []]]], 200),
    ]);

    $records = (new AirtableClient)->get('tblqVYQxgMXwgyY6r');

    expect($records)->toHaveCount(1);
    expect($airtableCalls())->toBe(2);
});

test('get throws instead of returning partial data when a later page fails', function () {
    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push(['records' => [['id' => 'r1', 'fields' => []]], 'offset' => 'page2'], 200)
            ->push('gateway timeout', 504)
            ->push('gateway timeout', 504)
            ->push('gateway timeout', 504),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    expect(fn () => (new AirtableClient)->get('tblqVYQxgMXwgyY6r'))
        ->toThrow(AirtableException::class);
});

test('patch sends correct data', function () {
    Http::fake([
        '*' => Http::response(['id' => 'rec123', 'fields' => []], 200),
    ]);

    $client = new AirtableClient;
    $result = $client->patch('tblqVYQxgMXwgyY6r', 'rec123', ['🚦სტატუსი' => '✅ დასრულებული']);

    expect($result)->toHaveKey('id');
    Http::assertSent(fn ($request) => $request->method() === 'PATCH');
});

test('post creates new record', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [['id' => 'recNEW', 'fields' => []]],
        ], 200),
    ]);

    $client = new AirtableClient;
    $result = $client->post('tbluQf2BlZvzHVDhu', ['📋 საკითხი' => 'ტესტი']);

    expect($result)->toHaveKey('records');
    Http::assertSent(fn ($request) => $request->method() === 'POST');
});

test('patch retries a 503 and then succeeds', function () use ($airtableCalls) {
    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push('unavailable', 503)
            ->push(['id' => 'rec123', 'fields' => []], 200),
    ]);

    $result = (new AirtableClient)->patch('tblqVYQxgMXwgyY6r', 'rec123', ['📥 ფოტოები' => true]);

    expect($result)->toHaveKey('id');
    expect($airtableCalls())->toBe(2);
});

test('patch throws when all retries fail', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response('unavailable', 503),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    expect(fn () => (new AirtableClient)->patch('tblqVYQxgMXwgyY6r', 'rec123', ['📥 ფოტოები' => true]))
        ->toThrow(AirtableException::class);
});

test('getRecord throws instead of returning an empty array', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response(['error' => 'NOT_FOUND'], 404),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    expect(fn () => (new AirtableClient)->getRecord('tblqVYQxgMXwgyY6r', 'recMissing'))
        ->toThrow(AirtableException::class);
});

test('post is never retried', function () use ($airtableCalls) {
    Http::fake([
        'api.airtable.com/*' => Http::response('unavailable', 503),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    expect(fn () => (new AirtableClient)->post('tblTest', ['📋 საკითხი' => 'ტესტი']))
        ->toThrow(AirtableException::class);

    expect($airtableCalls())->toBe(1);
});
