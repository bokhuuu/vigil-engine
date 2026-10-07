<?php

declare(strict_types=1);

use App\Business\Models\Business;
use App\Business\Models\Correspondence;
use App\Services\TelegramService;

it('marks pending correspondence as overdue when deadline has passed', function () {
    $business = Business::factory()->create();

    $corr = Correspondence::factory()->create([
        'business_id' => $business->id,
        'deadline_at' => now()->subDays(2),
        'status' => 'pending',
    ]);

    $this->mock(TelegramService::class)
        ->shouldReceive('sendToMe')
        ->zeroOrMoreTimes();

    $this->artisan('business:check-overdue')->assertExitCode(0);

    expect($corr->fresh()->status)->toBe('overdue');
});

it('does not mark correspondence as overdue when deadline has not passed', function () {
    $business = Business::factory()->create();

    $corr = Correspondence::create([
        'business_id' => $business->id,
        'round' => 1,
        'sent_at' => now()->subDays(2),
        'deadline_at' => now()->addDays(5),
        'status' => 'pending',
    ]);

    $this->mock(TelegramService::class)
        ->shouldReceive('sendToMe')
        ->zeroOrMoreTimes();

    $this->artisan('business:check-overdue')->assertExitCode(0);

    expect($corr->fresh()->status)->toBe('pending');
});
