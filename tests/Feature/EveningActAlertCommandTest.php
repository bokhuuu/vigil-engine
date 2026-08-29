<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

test('act alert groups acts by responsible team and sends individual messages', function () {
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
                        '📍სექტორი' => ['recS1'],
                        '👥 გუნდი' => ['recT1'],
                        '📅 აქტის მიღების თარიღი' => '2026-07-27',
                        '📞 მესაკუთრის კონტაქტი' => '555000001',
                    ]],
                ],
            ], 200)
            // getTeams
            ->push([
                'records' => [
                    ['id' => 'recT1', 'fields' => [
                        '👤 სახელი' => 'ჩადუ & გივიკო',
                        '🏷️ როლი' => 'ინსპექტორი',
                        '✈️ Telegram' => null,
                    ]],
                ],
            ], 200)
            // getSectors
            ->push([
                'records' => [
                    ['id' => 'recS1', 'fields' => [
                        '🔖 სახელი' => 'სექტორი 1-100',
                        '👥 გუნდები' => ['recT1'],
                    ]],
                ],
            ], 200)
            // getDoneActs
            ->push(['records' => []], 200)
            // getManagers
            ->push(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:act-alert')
        ->assertSuccessful();
});

test('act alert sends to group chat with counts', function () {
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
                        '📍სექტორი' => ['recS1'],
                        '👥 გუნდი' => ['recT1'],
                        '📅 აქტის მიღების თარიღი' => '2026-07-27',
                        '📞 მესაკუთრის კონტაქტი' => '555000001',
                    ]],
                ],
            ], 200)
            // getTeams
            ->push([
                'records' => [
                    ['id' => 'recT1', 'fields' => [
                        '👤 სახელი' => 'ჩადუ & გივიკო',
                        '🏷️ როლი' => 'ინსპექტორი',
                        '✈️ Telegram' => null,
                    ]],
                ],
            ], 200)
            // getSectors
            ->push([
                'records' => [
                    ['id' => 'recS1', 'fields' => [
                        '🔖 სახელი' => 'სექტორი 1-100',
                        '👥 გუნდები' => ['recT1'],
                    ]],
                ],
            ], 200)
            // getDoneActs
            ->push(['records' => []], 200)
            // getManagers
            ->push(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:act-alert')
        ->assertSuccessful();

    Http::assertSent(
        fn ($request) => str_contains($request->url(), 'api.telegram.org') &&
            $request['chat_id'] == config('vigil.telegram.group_chat_id')
    );
});
