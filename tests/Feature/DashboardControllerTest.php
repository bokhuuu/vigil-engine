<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config([
        'vigil.dashboard.user' => 'vigil',
        'vigil.dashboard.password' => 'test-password',
        'vigil.dashboard.cache_minutes' => 5,
        'vigil.dashboard.airtable_stats_url' => 'https://airtable.com/test',
    ]);
});

test('dashboard rejects request without credentials', function () {
    $response = $this->get('/dashboard');
    $response->assertStatus(401);
});

test('dashboard rejects request with wrong credentials', function () {
    $response = $this->get('/dashboard', [
        'Authorization' => 'Basic '.base64_encode('vigil:wrongpassword'),
    ]);
    $response->assertStatus(401);
});

test('dashboard returns 200 with correct credentials', function () {
    Cache::flush();

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [
                [
                    'id' => 'rec001',
                    'fields' => [
                        '🔖 აქტის ნომერი' => '329',
                        '🚦სტატუსი' => '⏳ მიმდინარე',
                        '❗რა აკლია აქტს' => 'ხელმოწერები',
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode('vigil:test-password'),
    ])->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewIs('dashboard');
});

test('dashboard filters by status incomplete', function () {
    Cache::flush();

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [
                [
                    'id' => 'rec001',
                    'fields' => [
                        '🔖 აქტის ნომერი' => '329',
                        '🚦სტატუსი' => '⏳ მიმდინარე',
                    ],
                ],
                [
                    'id' => 'rec002',
                    'fields' => [
                        '🔖 აქტის ნომერი' => '330',
                        '🚦სტატუსი' => '✅ დასრულებული',
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode('vigil:test-password'),
    ])->get('/dashboard?status=incomplete');

    $response->assertStatus(200);
    $response->assertViewHas('acts', fn ($acts) => $acts->count() === 1);
});

test('dashboard requiredFields returns only always-required for land-only act', function () {
    Cache::flush();

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [
                [
                    'id' => 'rec001',
                    'fields' => [
                        '🔖 აქტის ნომერი' => '329',
                        '🚦სტატუსი' => '⏳ მიმდინარე',
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode('vigil:test-password'),
    ])->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewHas('acts', function ($acts) {
        $required = $acts->first()['_required'];

        return $required === ['📥 ფოტოები', '📥 ხელმოწერები'];
    });
});

test('dashboard requiredFields includes შიდა ნახაზები when შენობა flag set', function () {
    Cache::flush();

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [
                [
                    'id' => 'rec001',
                    'fields' => [
                        '🔖 აქტის ნომერი' => '173',
                        '🚦სტატუსი' => '⏳ მიმდინარე',
                        '🏠 შენობა/ნაგებობები' => true,
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode('vigil:test-password'),
    ])->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewHas('acts', function ($acts) {
        $required = $acts->first()['_required'];

        return in_array('📥 შიდა ნახაზები', $required)
            && in_array('📥 ხარჯთაღრიცხვა', $required);
    });
});
