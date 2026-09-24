<?php

declare(strict_types=1);

use App\Services\ActCompletionService;

beforeEach(function () {
    config(['vigil.webhook.secret' => 'test-secret']);
});

test('act-complete rejects missing secret', function () {
    $this->mock(ActCompletionService::class)->shouldNotReceive('check');

    $this->postJson('/webhook/act-complete', ['record_id' => 'rec329'])
        ->assertStatus(401);
});

test('act-complete passes the record to the completion check', function () {
    $this->mock(ActCompletionService::class)
        ->shouldReceive('check')->with('rec329')->once()->andReturn([]);

    $this->postJson('/webhook/act-complete?secret=test-secret', ['record_id' => 'rec329'])
        ->assertOk();
});

test('act-complete ignores payload without record_id', function () {
    $this->mock(ActCompletionService::class)->shouldNotReceive('check');

    $this->postJson('/webhook/act-complete?secret=test-secret', [])
        ->assertOk();
});
