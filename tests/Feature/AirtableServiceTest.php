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

    expect($results)->toHaveCount(2);
});

test('getSummaryCounts returns correct counts', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [
                ['id' => 'rec001', 'fields' => [
                    '🚦სტატუსი' => '⏳ მიმდინარე',
                    '👤პასუხისმგებელი აქტზე' => ['ინსპექტორები'],
                    '👥 გუნდი' => ['recT1'],
                ]],
                ['id' => 'rec002', 'fields' => [
                    '🚦სტატუსი' => '✅ დასრულებული',
                    '👤პასუხისმგებელი აქტზე' => ['ამზომველები'],
                ]],
                ['id' => 'rec003', 'fields' => [
                    '🚦სტატუსი' => '⏳ მიმდინარე',
                    '👤პასუხისმგებელი აქტზე' => ['ამზომველები'],
                ]],
                ['id' => 'recT1', 'fields' => [
                    '👤 სახელი' => 'ჩადუ',
                    '🏷️ როლი' => 'ინსპექტორი',
                    '✈️ Telegram' => '111111',
                ]],
            ],
        ], 200),
    ]);

    $service = new AirtableService(new AirtableClient);
    $counts = $service->getSummaryCounts();

    expect($counts['total'])->toBe(4);
    expect($counts['done'])->toBe(1);
    expect($counts['incomplete'])->toBe(2);
    expect($counts['by_team']['ჩადუ'])->toBe(1);
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
