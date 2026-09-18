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

test('getActsWithoutDriveFolder returns acts with empty საქაღალდე', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [
                ['id' => 'rec001', 'fields' => ['🔖 აქტის ნომერი' => '001']],
                ['id' => 'rec002', 'fields' => ['🔖 აქტის ნომერი' => '002']],
            ],
        ], 200),
    ]);

    $service = new AirtableService(new AirtableClient);
    $results = $service->getActsWithoutDriveFolder();

    expect($results)->toHaveCount(2);
});

test('getActsWithoutDriveFolder respects limit', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [
                ['id' => 'rec001', 'fields' => ['🔖 აქტის ნომერი' => '001']],
            ],
        ], 200),
    ]);

    $service = new AirtableService(new AirtableClient);
    $results = $service->getActsWithoutDriveFolder(1);

    expect($results)->toHaveCount(1);
});

test('updateActDriveFolder patches საქაღალდე field', function () {
    Http::fake([
        '*' => Http::response(['id' => 'rec001', 'fields' => []], 200),
    ]);

    $service = new AirtableService(new AirtableClient);
    $service->updateActDriveFolder('rec001', 'https://drive.google.com/drive/folders/abc123');

    Http::assertSent(
        fn ($request) => $request->method() === 'PATCH' &&
            $request['fields']['💼 საქაღალდე'] === 'https://drive.google.com/drive/folders/abc123'
    );
});
