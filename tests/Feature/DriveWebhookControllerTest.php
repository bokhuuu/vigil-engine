<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['vigil.webhook.secret' => 'test-secret']);
});

test('drive webhook rejects request with invalid secret', function () {
    $response = $this->postJson('/webhook/drive?secret=wrong', [
        'act_number' => '329',
        'subfolder' => 'ფოტოები',
        'change_type' => 'added',
    ]);

    $response->assertStatus(401);
});

test('removed ფოტოები unticks checkbox and notifies managers', function () {
    config(['vigil.telegram.bot_token' => 'test-token', 'vigil.telegram.my_chat_id' => '999']);

    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            // getActByNumber
            ->push(['records' => [['id' => 'rec001', 'fields' => ['🔖 აქტის ნომერი' => '329']]]], 200)
            // untickActCheckbox
            ->push(['id' => 'rec001', 'fields' => []], 200)
            // getManagers → getTeams
            ->push(['records' => [['id' => 'm1', 'fields' => ['🏷️ როლი' => 'მენეჯერი', '✈️ Telegram' => '111']]]], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => '329',
        'subfolder' => 'ფოტოები',
        'change_type' => 'removed',
    ])->assertStatus(200);

    Http::assertSent(fn ($request) => $request->method() === 'PATCH'
        && $request['fields']['📥 ფოტოები'] === false);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.telegram.org')
        && $request['chat_id'] === '111');
});

test('removed სკანი notifies but does not patch Airtable', function () {
    config(['vigil.telegram.bot_token' => 'test-token', 'vigil.telegram.my_chat_id' => '999']);

    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push(['records' => [['id' => 'rec001', 'fields' => ['🔖 აქტის ნომერი' => '329']]]], 200)
            ->push(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => '329',
        'subfolder' => 'სკანი',
        'change_type' => 'removed',
    ])->assertStatus(200);

    Http::assertNotSent(fn ($request) => $request->method() === 'PATCH');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.telegram.org')
        && $request['chat_id'] === '999');
});

test('removed in untracked folder does nothing', function () {
    $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => '329',
        'subfolder' => 'კორესპონდენცია',
        'change_type' => 'removed',
    ])->assertStatus(200);

    Http::assertNothingSent();
});

test('removed for unknown ACT does not notify', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => '999',
        'subfolder' => 'ფოტოები',
        'change_type' => 'removed',
    ])->assertStatus(200);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.telegram.org'));
});

test('drive webhook ignores subfolders with no checkbox mapping', function () {
    $response = $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => '329',
        'subfolder' => 'სკანი',
        'change_type' => 'added',
    ]);

    $response->assertStatus(200);
    Http::assertNothingSent();
});

test('drive webhook ticks correct checkbox when file added to შიდა ნახაზები', function () {
    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            // getActByNumber
            ->push([
                'records' => [
                    ['id' => 'rec001', 'fields' => ['🔖 აქტის ნომერი' => '329']],
                ],
            ], 200)
            // tickActCheckbox
            ->push(['id' => 'rec001', 'fields' => []], 200),
    ]);

    $response = $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => '329',
        'subfolder' => 'შიდა ნახაზები',
        'change_type' => 'added',
    ]);

    $response->assertStatus(200);

    Http::assertSent(
        fn ($request) => $request->method() === 'PATCH' &&
            $request['fields']['📥 შიდა ნახაზები'] === true
    );
});

test('drive webhook ticks ფოტოები checkbox when file added to ფოტოები folder', function () {
    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push([
                'records' => [
                    ['id' => 'rec001', 'fields' => ['🔖 აქტის ნომერი' => '329']],
                ],
            ], 200)
            ->push(['id' => 'rec001', 'fields' => []], 200),
    ]);

    $response = $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => '329',
        'subfolder' => 'ფოტოები',
        'change_type' => 'added',
    ]);

    $response->assertStatus(200);

    Http::assertSent(
        fn ($request) => $request->method() === 'PATCH' &&
            $request['fields']['📥 ფოტოები'] === true
    );
});

test('drive webhook returns ok when act not found', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [],
        ], 200),
    ]);

    $response = $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => '999',
        'subfolder' => 'ხარჯთაღრიცხვა',
        'change_type' => 'added',
    ]);

    $response->assertStatus(200);
});

test('drive webhook returns ok when payload is missing fields', function () {
    $response = $this->postJson('/webhook/drive?secret=test-secret', []);

    $response->assertStatus(200);
    Http::assertNothingSent();
});

test('drive webhook reads subfolder mapping from config', function () {
    config(['vigil.drive.tracked_subfolders' => ['ტესტი' => '📥 ტესტი']]);

    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push([
                'records' => [
                    ['id' => 'rec001', 'fields' => ['🔖 აქტის ნომერი' => '329']],
                ],
            ], 200)
            ->push(['id' => 'rec001', 'fields' => []], 200),
    ]);

    $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => '329',
        'subfolder' => 'ტესტი',
        'change_type' => 'added',
    ])->assertStatus(200);

    Http::assertSent(
        fn ($request) => $request->method() === 'PATCH' &&
            $request['fields']['📥 ტესტი'] === true
    );
});

test('drive webhook ignores untracked subfolders like კორესპონდენცია', function () {
    $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => '329',
        'subfolder' => 'კორესპონდენცია',
        'change_type' => 'added',
    ])->assertStatus(200);

    Http::assertNothingSent();
});

test('drive webhook rejects everything when no secret is configured', function () {
    config(['vigil.webhook.secret' => null]);

    $this->postJson('/webhook/drive', [
        'act_number' => '329',
        'subfolder' => 'ფოტოები',
    ])->assertStatus(401);

    Http::assertNothingSent();
});

test('drive webhook accepts numeric act_number', function () {
    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push([
                'records' => [
                    ['id' => 'rec001', 'fields' => ['🔖 აქტის ნომერი' => '329']],
                ],
            ], 200)
            ->push(['id' => 'rec001', 'fields' => []], 200),
    ]);

    $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => 329,
        'subfolder' => 'ფოტოები',
        'change_type' => 'added',
    ])->assertStatus(200);

    Http::assertSent(fn ($request) => $request->method() === 'PATCH');
});

test('act-statuses rejects invalid secret', function () {
    $this->getJson('/webhook/act-statuses?secret=wrong')->assertStatus(401);

    Http::assertNothingSent();
});

test('act-statuses returns signature map for all ACTs', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [
                ['id' => 'r1', 'fields' => ['🔖 აქტის ნომერი' => '7-1', '📥 ხელმოწერები' => true]],
                ['id' => 'r2', 'fields' => ['🔖 აქტის ნომერი' => '329']],
                ['id' => 'r3', 'fields' => []],
            ],
        ], 200),
    ]);

    $this->getJson('/webhook/act-statuses?secret=test-secret')
        ->assertStatus(200)
        ->assertExactJson(['statuses' => ['7-1' => true, '329' => false]]);
});

test('act-statuses is cached between calls', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [
                ['id' => 'r1', 'fields' => ['🔖 აქტის ნომერი' => '329', '📥 ხელმოწერები' => true]],
            ],
        ], 200),
    ]);

    $this->getJson('/webhook/act-statuses?secret=test-secret')->assertStatus(200);
    $this->getJson('/webhook/act-statuses?secret=test-secret')->assertStatus(200);

    Http::assertSentCount(1);
});
