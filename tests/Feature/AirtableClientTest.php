<?php

use App\Services\AirtableClient;
use Illuminate\Support\Facades\Http;

test('get returns records from airtable', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [
                ['id' => 'rec123', 'fields' => ['🔖 აქტის ნომერი' => 'ACT-001']],
                ['id' => 'rec456', 'fields' => ['🔖 აქტის ნომერი' => 'ACT-002']],
            ],
        ], 200),
    ]);

    $client  = new AirtableClient();
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

    $client  = new AirtableClient();
    $records = $client->get('tblqVYQxgMXwgyY6r');

    expect($records)->toHaveCount(2);
    expect($records[0]['id'])->toBe('rec001');
    expect($records[1]['id'])->toBe('rec002');
});

test('get returns empty array on failure', function () {
    Http::fake([
        '*' => Http::response([], 401),
    ]);

    $client  = new AirtableClient();
    $records = $client->get('tblqVYQxgMXwgyY6r');

    expect($records)->toBeArray()->toBeEmpty();
});

test('patch sends correct data', function () {
    Http::fake([
        '*' => Http::response(['id' => 'rec123', 'fields' => []], 200),
    ]);

    $client = new AirtableClient();
    $result = $client->patch('tblqVYQxgMXwgyY6r', 'rec123', ['🚦სტატუსი' => '✅ დასრულებული']);

    expect($result)->toHaveKey('id');
    Http::assertSent(fn($request) => $request->method() === 'PATCH');
});

test('post creates new record', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [['id' => 'recNEW', 'fields' => []]],
        ], 200),
    ]);

    $client = new AirtableClient();
    $result = $client->post('tbluQf2BlZvzHVDhu', ['📋 საკითხი' => 'ტესტი']);

    expect($result)->toHaveKey('records');
    Http::assertSent(fn($request) => $request->method() === 'POST');
});
