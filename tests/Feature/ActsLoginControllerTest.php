<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    Http::preventStrayRequests();
    RateLimiter::clear('dashboard/login|127.0.0.1');
    config([
        'vigil.dashboard.user' => 'vigil',
        'vigil.dashboard.password' => 'test-password',
        'vigil.dashboard.uploader_user' => 'colliers',
        'vigil.dashboard.uploader_password' => 'upload-password',
        'vigil.dashboard.viewer_user' => 'viewer',
        'vigil.dashboard.viewer_password' => 'view-password',
    ]);
});

// ── GET /dashboard/login ──────────────────────────────────────────────────────

test('login page renders for guest', function () {
    $this->get(route('acts.login'))->assertStatus(200)->assertViewIs('acts.login');
});

test('login page redirects to dashboard if already logged in', function () {
    $this->withSession(['vigil_role' => 'manager'])
        ->get(route('acts.login'))
        ->assertRedirect(route('dashboard'));
});

// ── POST /dashboard/login — wrong credentials ─────────────────────────────────

test('login rejects wrong username', function () {
    $response = $this->post(route('acts.login'), ['username' => 'nobody', 'password' => 'test-password']);
    $response->assertRedirect(route('acts.login'));
    expect(session('errors'))->not->toBeNull();
});

test('login rejects wrong password', function () {
    $response = $this->post(route('acts.login'), ['username' => 'vigil', 'password' => 'wrong']);
    $response->assertRedirect(route('acts.login'));
    expect(session('errors'))->not->toBeNull();
});

test('login rejects empty credentials', function () {
    $response = $this->post(route('acts.login'), ['username' => '', 'password' => '']);
    $response->assertRedirect(route('acts.login'));
    expect(session('errors'))->not->toBeNull();
});

// ── POST /dashboard/login — correct credentials ───────────────────────────────

test('manager credentials log in with manager role', function () {
    $this->post(route('acts.login'), ['username' => 'vigil', 'password' => 'test-password'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('vigil_role', 'manager');
});

test('uploader credentials log in with uploader role', function () {
    $this->post(route('acts.login'), ['username' => 'colliers', 'password' => 'upload-password'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('vigil_role', 'uploader');
});

test('viewer credentials log in with viewer role', function () {
    $this->post(route('acts.login'), ['username' => 'viewer', 'password' => 'view-password'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('vigil_role', 'viewer');
});

// ── POST /dashboard/logout ────────────────────────────────────────────────────

test('logout clears session and redirects to login', function () {
    $this->withSession(['vigil_role' => 'manager'])
        ->post(route('acts.logout'))
        ->assertRedirect(route('acts.login'));

    $this->assertGuest();
});

test('logout removes vigil_role from session', function () {
    $this->withSession(['vigil_role' => 'manager'])
        ->post(route('acts.logout'))
        ->assertSessionMissing('vigil_role');
});

test('login is throttled after 10 failed attempts', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('acts.login'), ['username' => 'nobody', 'password' => 'wrong']);
    }

    $this->post(route('acts.login'), ['username' => 'nobody', 'password' => 'wrong'])
        ->assertStatus(429);
});

test('logout invalidates the session so old session ID is gone', function () {
    $this->withSession(['vigil_role' => 'manager']);
    $oldId = session()->getId();

    $this->post(route('acts.logout'));

    expect(session()->getId())->not->toBe($oldId);
});
