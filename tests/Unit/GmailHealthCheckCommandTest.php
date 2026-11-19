<?php

declare(strict_types=1);

use App\Console\Commands\Business\GmailHealthCheckCommand;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

covers(GmailHealthCheckCommand::class);

beforeEach(function (): void {
    Cache::forget('business_gmail_last_run');
    Cache::forget('business_gmail_health_down');
});

it('sends alert when cache key is missing', function (): void {
    $telegram = Mockery::mock(TelegramService::class);
    $telegram->shouldReceive('sendToMe')->once()->withArgs(
        fn (string $msg) => str_contains($msg, 'business:watch-gmail') && str_contains($msg, '🔴')
    );

    $this->app->instance(TelegramService::class, $telegram);
    $this->artisan('business:gmail-check')->assertExitCode(1);
});

it('skips alert when already marked down', function (): void {
    Cache::put('business_gmail_health_down', true, 300);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->shouldReceive('sendToMe')->never();

    $this->app->instance(TelegramService::class, $telegram);
    $this->artisan('business:gmail-check')->assertExitCode(1);
});

it('sends recovery alert when watcher resumes', function (): void {
    Cache::put('business_gmail_last_run', now(), 300);
    Cache::put('business_gmail_health_down', true, 300);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->shouldReceive('sendToMe')->once()->withArgs(
        fn (string $msg) => str_contains($msg, '✅')
    );

    $this->app->instance(TelegramService::class, $telegram);
    $this->artisan('business:gmail-check')->assertExitCode(0);
});

it('reports healthy when watcher ran recently and was not down', function (): void {
    Cache::put('business_gmail_last_run', now(), 300);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->shouldReceive('sendToMe')->never();

    $this->app->instance(TelegramService::class, $telegram);
    $this->artisan('business:gmail-check')->assertExitCode(0);
});
