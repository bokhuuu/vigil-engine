<?php

declare(strict_types=1);

beforeEach(function (): void {
    $user = config('vigil.dashboard.user');
    $pass = config('vigil.dashboard.password');

    $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode("{$user}:{$pass}"),
    ]);
});

it('rejects an invalid record id pattern', function (): void {
    $this->post('/dashboard/acts/NOT_VALID/excel-entered')
        ->assertStatus(404);
})->group('excel');
