<?php

use Illuminate\Support\Facades\Http;

test('act alert command runs successfully with no incomplete acts', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:act-alert')
        ->assertSuccessful();
});

test('act alert command sends no incomplete message when all acts done', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:act-alert')
        ->assertSuccessful();

    Http::assertSent(
        fn ($request) => str_contains($request->url(), 'api.telegram.org') &&
            str_contains($request['text'], 'არასრული აქტი არ არის')
    );
});

test('act alert groups acts by responsible team', function () {
    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            // getIncompleteActs
            ->push([
                'records' => [
                    ['id' => 'rec001', 'fields' => [
                        '🔖 აქტის ნომერი' => 'ACT-001',
                        '🚦სტატუსი' => '⏳ მიმდინარე',
                        '👤პასუხისმგებელი აქტზე' => ['ინსპექტორები'],
                        '❗რა აკლია აქტს' => 'ფოტოები',
                        '📍სექტორი' => ['1-1'],
                        '📅 აქტის მიღების თარიღი' => '2026-07-27',
                        '📞 მესაკუთრის კონტაქტი' => '555000001',
                    ]],
                ],
            ], 200)
            // getTeams
            ->push([
                'records' => [
                    ['id' => 'recT1', 'fields' => [
                        '👤 სახელი' => 'ჩადუ',
                        '✈️ Telegram' => null,
                    ]],
                ],
            ], 200)
            // logNotification POST
            ->push(['records' => [['id' => 'recN1', 'fields' => []]]], 200)
            // markActNotified PATCH
            ->push(['id' => 'rec001', 'fields' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:act-alert')
        ->assertSuccessful();

    Http::assertSent(
        fn ($request) => str_contains($request->url(), 'api.telegram.org')
    );
});

test('act alert sends to group chat', function () {
    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push([
                'records' => [
                    ['id' => 'rec001', 'fields' => [
                        '🔖 აქტის ნომერი' => 'ACT-001',
                        '🚦სტატუსი' => '⏳ მიმდინარე',
                        '👤პასუხისმგებელი აქტზე' => ['ინსპექტორები'],
                        '❗რა აკლია აქტს' => 'ფოტოები',
                        '📍სექტორი' => ['1-1'],
                        '📅 აქტის მიღების თარიღი' => '2026-07-27',
                        '📞 მესაკუთრის კონტაქტი' => '555000001',
                    ]],
                ],
            ], 200)
            ->push(['records' => []], 200)
            ->push(['records' => [['id' => 'recN1', 'fields' => []]]], 200)
            ->push(['id' => 'rec001', 'fields' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:act-alert')
        ->assertSuccessful();

    Http::assertSent(
        fn ($request) => str_contains($request->url(), 'api.telegram.org') &&
            $request['chat_id'] == config('vigil.telegram.group_chat_id')
    );
});
