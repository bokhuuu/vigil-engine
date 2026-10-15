<?php

declare(strict_types=1);

use App\Repositories\DriveWatcherStateRepository;
use App\Services\DriveChangesReader;
use App\Services\DriveWatcherService;
use App\Services\TelegramService;

beforeEach(function (): void {
    $this->path = sys_get_temp_dir().'/vigil-watch-cmd-'.uniqid().'.json';
    $this->state = new DriveWatcherStateRepository($this->path);
    $this->app->instance(DriveWatcherStateRepository::class, $this->state);
});

afterEach(function (): void {
    if (file_exists($this->path)) {
        unlink($this->path);
    }
});

it('fails if no page token and not seeding', function (): void {
    $this->artisan('vigil:drive-watch')
        ->expectsOutputToContain('No page token')
        ->assertExitCode(1);
});

it('runs seed and sets page token', function (): void {
    $watcher = Mockery::mock(DriveWatcherService::class);
    $watcher->shouldReceive('seedFromAirtable')->once()->andReturn(5);
    $watcher->shouldReceive('initPageToken')->once();
    $this->app->instance(DriveWatcherService::class, $watcher);

    $this->artisan('vigil:drive-watch --seed')
        ->expectsOutputToContain('Seeded 5 ACT folders')
        ->assertExitCode(0);
});

it('processes changes in dry run mode', function (): void {
    $this->state->setPageToken('token-abc');

    $reader = Mockery::mock(DriveChangesReader::class);
    $reader->shouldReceive('getChangedIds')->once()->andReturn(['id1', 'id2']);
    $this->app->instance(DriveChangesReader::class, $reader);

    $watcher = Mockery::mock(DriveWatcherService::class);
    $watcher->shouldReceive('process')->once()->with(['id1', 'id2'], true);
    $this->app->instance(DriveWatcherService::class, $watcher);

    $this->artisan('vigil:drive-watch --dry-run')
        ->assertExitCode(0);
});

it('sends telegram alert on exception', function (): void {
    $this->state->setPageToken('token-abc');

    $reader = Mockery::mock(DriveChangesReader::class);
    $reader->shouldReceive('getChangedIds')->andThrow(new Exception('API down'));
    $this->app->instance(DriveChangesReader::class, $reader);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->shouldReceive('sendToMe')->once()->withArgs(fn ($msg) => str_contains($msg, 'API down'));
    $this->app->instance(TelegramService::class, $telegram);

    $this->artisan('vigil:drive-watch')
        ->assertExitCode(1);
});
