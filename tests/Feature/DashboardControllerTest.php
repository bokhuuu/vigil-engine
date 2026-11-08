<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
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

// ── Auth ──────────────────────────────────────────────────────────────────────

test('dashboard rejects request without session', function () {
    $this->get('/dashboard')->assertRedirect(route('acts.login'));
});

test('dashboard rejects request with invalid session role', function () {
    $this->withSession(['vigil_role' => 'hacker'])
        ->get('/dashboard')
        ->assertRedirect(route('acts.login'));
});

// ── 200 responses ─────────────────────────────────────────────────────────────

test('dashboard returns 200 with manager session', function () {
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

    $this->withSession(['vigil_role' => 'manager'])
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertViewIs('acts.dashboard');
});

// ── Filtering ─────────────────────────────────────────────────────────────────

test('dashboard filters by status incomplete', function () {
    Cache::flush();

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [
                [
                    'id' => 'rec001',
                    'fields' => ['🔖 აქტის ნომერი' => '329', '🚦სტატუსი' => '⏳ მიმდინარე'],
                ],
                [
                    'id' => 'rec002',
                    'fields' => ['🔖 აქტის ნომერი' => '330', '🚦სტატუსი' => '✅ დასრულებული'],
                ],
            ],
        ], 200),
    ]);

    $this->withSession(['vigil_role' => 'manager'])
        ->get('/dashboard?status=incomplete')
        ->assertStatus(200)
        ->assertViewHas('acts', fn ($acts) => $acts->count() === 1);
});

// ── Required fields ───────────────────────────────────────────────────────────

test('dashboard requiredFields returns only always-required for land-only act', function () {
    Cache::flush();

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [
                [
                    'id' => 'rec001',
                    'fields' => ['🔖 აქტის ნომერი' => '329', '🚦სტატუსი' => '⏳ მიმდინარე'],
                ],
            ],
        ], 200),
    ]);

    $this->withSession(['vigil_role' => 'manager'])
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertViewHas('acts', function ($acts) {
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

    $this->withSession(['vigil_role' => 'manager'])
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertViewHas('acts', function ($acts) {
            $required = $acts->first()['_required'];

            return in_array('📥 შიდა ნახაზები', $required)
                && in_array('📥 ხარჯთაღრიცხვა', $required);
        });
});

// ── Fetch time ────────────────────────────────────────────────────────────────

test('dashboard shows the real fetch time', function () {
    Cache::flush();
    $this->travelTo(Carbon::parse('2026-09-24 14:32:00', config('app.timezone')));

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [['id' => 'r1', 'fields' => ['🔖 აქტის ნომერი' => '329']]],
        ], 200),
    ]);

    $this->withSession(['vigil_role' => 'manager'])
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('განახლდა 14:32');
});

test('refreshing the page does not change the fetch time', function () {
    Cache::flush();

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [['id' => 'r1', 'fields' => ['🔖 აქტის ნომერი' => '329']]],
        ], 200),
    ]);

    $session = ['vigil_role' => 'manager'];

    $this->travelTo(Carbon::parse('2026-09-24 14:32:00', config('app.timezone')));
    $this->withSession($session)->get('/dashboard')->assertSee('განახლდა 14:32');

    $this->travelTo(Carbon::parse('2026-09-24 14:34:00', config('app.timezone')));
    $this->withSession($session)->get('/dashboard')->assertSee('განახლდა 14:32');
});

// ── Fallback ──────────────────────────────────────────────────────────────────

test('dashboard falls back to last good data when Airtable fails', function () {
    Cache::flush();
    config([
        'vigil.airtable.retry_sleep_ms' => 0,
        'vigil.telegram.bot_token' => 'test-token',
        'vigil.telegram.my_chat_id' => '999',
    ]);

    Http::fake([
        'api.airtable.com/*' => Http::sequence()
            ->push(['records' => [['id' => 'r1', 'fields' => ['🔖 აქტის ნომერი' => '329']]]], 200)
            ->push('unavailable', 503)
            ->push('unavailable', 503)
            ->push('unavailable', 503),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $session = ['vigil_role' => 'manager'];

    $this->withSession($session)->get('/dashboard')->assertOk();

    Cache::forget('dashboard_snapshot');

    $this->withSession($session)->get('/dashboard')
        ->assertOk()
        ->assertSee('Airtable მიუწვდომელია')
        ->assertSee('329');
});

// ── HTML structure ────────────────────────────────────────────────────────────

test('dashboard head has no stray text and loads fonts via link tag', function () {
    Cache::flush();

    Http::fake([
        'api.airtable.com/*' => Http::response(['records' => []], 200),
    ]);

    $html = $this->withSession(['vigil_role' => 'manager'])
        ->get('/dashboard')
        ->getContent();

    expect($html)->toContain('<link rel="stylesheet"');
    expect($html)->not->toMatch('/>\s*href="https:\/\/fonts\.googleapis\.com/');
});

test('dashboard links each ACT number to its Airtable record', function () {
    Cache::flush();

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [['id' => 'rec329', 'fields' => ['🔖 აქტის ნომერი' => '329']]],
        ], 200),
    ]);

    $expected = 'https://airtable.com/'
        .config('vigil.airtable.base_id').'/'
        .config('vigil.airtable.tables.acts').'/rec329';

    $this->withSession(['vigil_role' => 'manager'])
        ->get('/dashboard')
        ->assertOk()
        ->assertSee($expected, false);
});
