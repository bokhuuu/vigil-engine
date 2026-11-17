<?php

declare(strict_types=1);

use App\Jobs\AirtableSnapshotJob;
use App\Services\AirtableService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

it('stores snapshot and last_good in cache', function () {
    $acts = [['id' => 'rec1', 'fields' => ['🔖 აქტის ნომერი' => '1']]];

    $airtable = Mockery::mock(AirtableService::class);
    $airtable->shouldReceive('getAllActs')->once()->andReturn($acts);

    Cache::shouldReceive('put')
        ->once()
        ->with('dashboard_snapshot', Mockery::on(fn ($v) => $v['acts'] === $acts), 35 * 60);

    Cache::shouldReceive('forever')
        ->once()
        ->with('dashboard_snapshot_last_good', Mockery::on(fn ($v) => $v['acts'] === $acts));

    $job = new AirtableSnapshotJob;
    $job->handle($airtable);
});
