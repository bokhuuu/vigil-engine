<?php

use Illuminate\Support\Facades\Http;

test('correspondence overdue command runs successfully with no overdue', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:correspondence-overdue')
        ->assertSuccessful();
});

test('correspondence overdue command marks overdue and sends alert', function () {
    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push(['records' => [
                ['id' => 'recC1', 'fields' => [
                    '📋 საკითხი' => 'გზების მოთხოვნა',
                    '⏰ ვადა' => '2026-07-01',
                    '🚦სტატუსი' => '⏳ მუშავდება',
                ]],
            ]], 200)
            ->push(['id' => 'recC1', 'fields' => []], 200) // PATCH response
            ->push(['records' => [
                ['id' => 'recC1', 'fields' => [
                    '📋 საკითხი' => 'გზების მოთხოვნა',
                    '⏰ ვადა' => '2026-07-01',
                ]],
            ]], 200) // getAlreadyOverdueCorrespondence
            ->push(['records' => []], 200), // getManagers
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:correspondence-overdue')
        ->assertSuccessful();

    Http::assertSent(
        fn ($request) => $request->method() === 'PATCH' &&
            str_contains($request->url(), 'recC1')
    );
});

test('correspondence overdue command sends telegram alert when overdue found', function () {
    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push(['records' => [
                ['id' => 'recC1', 'fields' => [
                    '📋 საკითხი' => 'გზების მოთხოვნა',
                    '⏰ ვადა' => '2026-07-01',
                    '🚦სტატუსი' => '⏳ მუშავდება',
                ]],
            ]], 200)
            ->push(['id' => 'recC1', 'fields' => []], 200)
            ->push(['records' => [
                ['id' => 'recC1', 'fields' => [
                    '📋 საკითხი' => 'გზების მოთხოვნა',
                    '⏰ ვადა' => '2026-07-01',
                ]],
            ]], 200) // getAlreadyOverdueCorrespondence
            ->push(['records' => []], 200), // getManagers
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:correspondence-overdue')
        ->assertSuccessful();

    Http::assertSent(
        fn ($request) => str_contains($request->url(), 'api.telegram.org')
    );
});
