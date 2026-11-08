<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('redirects to acts.login when no session role', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect(route('acts.login'));
});

it('redirects to acts.login when session role is invalid', function () {
    $response = $this->withSession(['vigil_role' => 'hacker'])->get('/dashboard');
    $response->assertRedirect(route('acts.login'));
});

it('allows manager through', function () {
    Http::fake(['api.airtable.com/*' => Http::response(['records' => []], 200)]);
    $this->withSession(['vigil_role' => 'manager'])->get('/dashboard')->assertStatus(200);
});

it('allows uploader through', function () {
    Http::fake(['api.airtable.com/*' => Http::response(['records' => []], 200)]);
    $this->withSession(['vigil_role' => 'uploader'])->get('/dashboard')->assertStatus(200);
});

it('allows viewer through', function () {
    Http::fake(['api.airtable.com/*' => Http::response(['records' => []], 200)]);
    $this->withSession(['vigil_role' => 'viewer'])->get('/dashboard')->assertStatus(200);
});
