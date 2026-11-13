<?php

declare(strict_types=1);

use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Tests\TestCase;

uses(TestCase::class);

function makeHorizonSupervisor(string $status): object
{
    return new class($status)
    {
        public function __construct(public string $status) {}
    };
}

test('horizon check: does nothing when running and was not down', function () {
    $repo = Mockery::mock(MasterSupervisorRepository::class);
    $repo->allows('all')->andReturn([makeHorizonSupervisor('running')]);
    app()->instance(MasterSupervisorRepository::class, $repo);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToMe')->never();

    Cache::forget('horizon_health_down');

    $this->artisan('vigil:horizon-check')->assertExitCode(0);
});

test('horizon check: alerts once when horizon goes down', function () {
    $repo = Mockery::mock(MasterSupervisorRepository::class);
    $repo->allows('all')->andReturn([makeHorizonSupervisor('stopped')]);
    app()->instance(MasterSupervisorRepository::class, $repo);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToMe')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'Horizon გაჩერებულია'))
    );
    app()->instance(TelegramService::class, $telegram);

    Cache::forget('horizon_health_down');

    $this->artisan('vigil:horizon-check')->assertExitCode(0);

    expect(Cache::get('horizon_health_down'))->toBeTrue();
});

test('horizon check: does not re-alert when already marked down', function () {
    $repo = Mockery::mock(MasterSupervisorRepository::class);
    $repo->allows('all')->andReturn([makeHorizonSupervisor('stopped')]);
    app()->instance(MasterSupervisorRepository::class, $repo);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToMe')->never();
    app()->instance(TelegramService::class, $telegram);

    Cache::put('horizon_health_down', true, now()->addHours(24));

    $this->artisan('vigil:horizon-check')->assertExitCode(0);
});

test('horizon check: alerts recovery when horizon comes back up', function () {
    $repo = Mockery::mock(MasterSupervisorRepository::class);
    $repo->allows('all')->andReturn([makeHorizonSupervisor('running')]);
    app()->instance(MasterSupervisorRepository::class, $repo);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToMe')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'Horizon აღდგა'))
    );
    app()->instance(TelegramService::class, $telegram);

    Cache::put('horizon_health_down', true, now()->addHours(24));

    $this->artisan('vigil:horizon-check')->assertExitCode(0);

    expect(Cache::get('horizon_health_down'))->toBeNull();
});

test('horizon check: treats empty supervisor list as down', function () {
    $repo = Mockery::mock(MasterSupervisorRepository::class);
    $repo->allows('all')->andReturn([]);
    app()->instance(MasterSupervisorRepository::class, $repo);

    $telegram = Mockery::mock(TelegramService::class);
    $telegram->expects('sendToMe')->once()->with(
        Mockery::on(fn (string $msg) => str_contains($msg, 'Horizon გაჩერებულია'))
    );
    app()->instance(TelegramService::class, $telegram);

    Cache::forget('horizon_health_down');

    $this->artisan('vigil:horizon-check')->assertExitCode(0);
});
