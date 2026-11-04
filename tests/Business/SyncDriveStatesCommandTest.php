<?php

declare(strict_types=1);

namespace Tests\Business;

use App\Business\Models\Business;
use App\Business\Services\BusinessDriveService;
use App\Console\Commands\Business\SyncDriveStates;

class SyncDriveStatesCommandTest extends BusinessTestCase
{
    public function test_command_calls_sync_for_each_business_with_drive_folder(): void
    {
        Business::create([
            'name' => 'Business A',
            'registration_code' => '111111111',
            'lot_number' => 1,
            'type' => ['მეპატრონე'],
            'status' => 'active',
            'drive_folder_url' => 'https://drive.google.com/drive/folders/aaa',
        ]);

        Business::create([
            'name' => 'Business B',
            'registration_code' => '222222222',
            'lot_number' => 2,
            'type' => ['მოიჯარე'],
            'status' => 'active',
            'drive_folder_url' => 'https://drive.google.com/drive/folders/bbb',
        ]);

        $driveService = \Mockery::mock(BusinessDriveService::class);
        $driveService->shouldReceive('syncSubfolderStates')->twice();

        $command = new SyncDriveStates($driveService);
        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $this->artisan('business:sync-drive-states')
            ->assertSuccessful()
            ->expectsOutputToContain('Synced 2 of 2 businesses.');
    }

    public function test_command_skips_businesses_without_drive_folder(): void
    {
        Business::create([
            'name' => 'No Drive Business',
            'registration_code' => '333333333',
            'lot_number' => 3,
            'type' => ['სხვა'],
            'status' => 'active',
            'drive_folder_url' => null,
        ]);

        $driveService = \Mockery::mock(BusinessDriveService::class);
        $driveService->shouldReceive('syncSubfolderStates')->never();

        $command = new SyncDriveStates($driveService);
        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $this->artisan('business:sync-drive-states')
            ->assertSuccessful()
            ->expectsOutputToContain('No businesses with Drive folders found.');
    }

    public function test_command_continues_after_single_business_failure(): void
    {
        Business::create([
            'name' => 'Good Business',
            'registration_code' => '444444444',
            'lot_number' => 4,
            'type' => ['მეპატრონე'],
            'status' => 'active',
            'drive_folder_url' => 'https://drive.google.com/drive/folders/good',
        ]);

        Business::create([
            'name' => 'Failing Business',
            'registration_code' => '555555555',
            'lot_number' => 5,
            'type' => ['მეპატრონე'],
            'status' => 'active',
            'drive_folder_url' => 'https://drive.google.com/drive/folders/fail',
        ]);

        $callCount = 0;

        $driveService = \Mockery::mock(BusinessDriveService::class);
        $driveService->shouldReceive('syncSubfolderStates')
            ->twice()
            ->andReturnUsing(function () use (&$callCount): void {
                $callCount++;
                if ($callCount === 1) {
                    throw new \RuntimeException('Drive API error');
                }
            });

        $command = new SyncDriveStates($driveService);
        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $this->artisan('business:sync-drive-states')
            ->assertSuccessful()
            ->expectsOutputToContain('Synced 1 of 2 businesses.');
    }
}
