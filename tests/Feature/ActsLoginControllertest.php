<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
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
    $this->post(route('acts.login'), ['username' => 'nobody', 'password' => 'test-password'])
        ->assertRedirect(route('acts.login'))
        ->assertSessionHasErrors('credentials');
});

test('login rejects wrong password', function () {
    $this->post(route('acts.login'), ['username' => 'vigil', 'password' => 'wrong'])
        ->assertRedirect(route('acts.login'))
        ->assertSessionHasErrors('credentials');
});

test('login rejects empty credentials', function () {
    $this->post(route('acts.login'), ['username' => '', 'password' => ''])
        ->assertRedirect(route('acts.login'))
        ->assertSessionHasErrors('credentials');
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
