<?php

use Illuminate\Support\Facades\Http;

test('daily summary command runs successfully', function () {
    Http::fake([
        '*' => Http::response([
            'records' => [
                ['id' => 'rec001', 'fields' => [
                    '🚦სტატუსი' => '⏳ მიმდინარე',
                    '👤პასუხისმგებელი აქტზე' => ['ინსპექტორები'],
                ]],
                ['id' => 'rec002', 'fields' => [
                    '🚦სტატუსი' => '✅ დასრულებული',
                ]],
            ],
        ], 200),
    ]);

    $this->artisan('vigil:daily-summary')
        ->assertSuccessful();
});

test('daily summary command sends telegram message', function () {
    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('vigil:daily-summary')
        ->assertSuccessful();

    Http::assertSent(
        fn ($request) => str_contains($request->url(), 'api.telegram.org')
    );
});
