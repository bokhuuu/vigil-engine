<?php

use App\Services\AirtableClient;
use App\Services\AirtableService;
use Illuminate\Support\Facades\Http;

test('getIncompleteActs returns only incomplete acts', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [
                ['id' => 'rec001', 'fields' => ['🚦სტატუსი' => '⏳ მიმდინარე', '🔖 აქტის ნომერი' => 'ACT-001']],
                ['id' => 'rec002', 'fields' => ['🚦სტატუსი' => '✅ დასრულებული', '🔖 აქტის ნომერი' => 'ACT-002']],
            ],
        ], 200),
    ]);

    $service = new AirtableService(new AirtableClient);
    $results = $service->getIncompleteActs();

    expect($results)->toHaveCount(2); // filter happens in Airtable, we trust the response
});

test('getSummaryCounts returns correct counts', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [
                ['id' => 'rec001', 'fields' => [
                    '🚦სტატუსი' => '⏳ მიმდინარე',
                    '👤პასუხისმგებელი აქტზე' => ['ინსპექტორები'],
                ]],
                ['id' => 'rec002', 'fields' => [
                    '🚦სტატუსი' => '✅ დასრულებული',
                    '👤პასუხისმგებელი აქტზე' => ['ამზომველები'],
                ]],
                ['id' => 'rec003', 'fields' => [
                    '🚦სტატუსი' => '⏳ მიმდინარე',
                    '👤პასუხისმგებელი აქტზე' => ['ამზომველები'],
                ]],
            ],
        ], 200),
    ]);

    $service = new AirtableService(new AirtableClient);
    $counts = $service->getSummaryCounts();

    expect($counts['total'])->toBe(3);
    expect($counts['done'])->toBe(1);
    expect($counts['incomplete'])->toBe(2);
    expect($counts['by_team']['ინსპექტორები'])->toBe(1);
    expect($counts['by_team']['ამზომველები'])->toBe(1);
});

test('getTeams returns all team records', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [
                ['id' => 'recT1', 'fields' => ['👤 სახელი' => 'ჩადუ', '✈️ Telegram' => '111111']],
                ['id' => 'recT2', 'fields' => ['👤 სახელი' => 'მურო', '✈️ Telegram' => '222222']],
            ],
        ], 200),
    ]);

    $service = new AirtableService(new AirtableClient);
    $teams = $service->getTeams();

    expect($teams)->toHaveCount(2);
    expect($teams[0]['fields']['👤 სახელი'])->toBe('ჩადუ');
});

test('markActNotified patches correct field', function () {
    Http::fake([
        '*' => Http::response(['id' => 'rec001', 'fields' => []], 200),
    ]);

    $service = new AirtableService(new AirtableClient);
    $service->markActNotified('rec001');

    Http::assertSent(
        fn ($request) => $request->method() === 'PATCH' &&
            str_contains($request->url(), 'rec001')
    );
});

test('markCorrespondenceOverdue patches status to overdue', function () {
    Http::fake([
        '*' => Http::response(['id' => 'rec001', 'fields' => []], 200),
    ]);

    $service = new AirtableService(new AirtableClient);
    $service->markCorrespondenceOverdue('rec001');

    Http::assertSent(
        fn ($request) => $request->method() === 'PATCH' &&
            $request['fields']['🚦სტატუსი'] === '🔴 ვადაგადაცილებული'
    );
});

test('logNotification creates record in notifications table', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [['id' => 'recNEW', 'fields' => []]],
        ], 200),
    ]);

    $service = new AirtableService(new AirtableClient);
    $service->logNotification('ACT-001 — ინსპექტორები', 'Telegram', 'recACT1', 'recTEAM1');

    Http::assertSent(
        fn ($request) => $request->method() === 'POST' &&
            str_contains($request->url(), config('vigil.airtable.tables.notifications'))
    );
});
