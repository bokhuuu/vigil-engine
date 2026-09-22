<?php

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

test('drive webhook ignores removed change type', function () {
    $response = $this->postJson('/webhook/drive?secret=test-secret', [
        'act_number' => '329',
        'subfolder' => 'ფოტოები',
        'change_type' => 'removed',
    ]);

    $response->assertStatus(200);
    Http::assertNothingSent();
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
