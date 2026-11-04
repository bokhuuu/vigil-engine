<?php

declare(strict_types=1);

namespace Tests\Business;

use App\Business\Models\Business;
use App\Business\Models\BusinessDriveState;
use App\Business\Services\BusinessDriveService;
use App\Services\GoogleDriveService;
use Mockery\MockInterface;

class BusinessDriveServiceSyncTest extends BusinessTestCase
{
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Test Business',
            'registration_code' => '123456789',
            'lot_number' => 1,
            'type' => ['მეპატრონე'],
            'status' => 'active',
            'drive_folder_url' => 'https://drive.google.com/drive/folders/root123',
        ]);
    }

    public function test_sync_upserts_one_row_per_subfolder(): void
    {
        $subfolders = config('vigil.business.drive_subfolders');
        $expected = count($subfolders) + 1; // +1 for მიმოწერა

        $this->mock(GoogleDriveService::class, function (MockInterface $mock) use ($subfolders): void {
            $mock->shouldReceive('findOrCreateFolder')
                ->times(count($subfolders) + 1)
                ->andReturn('subfolder_id_123');

            $mock->shouldReceive('listFilesInFolder')
                ->times(count($subfolders) + 1)
                ->andReturn([]);
        });

        app(BusinessDriveService::class)->syncSubfolderStates($this->business);

        $this->assertDatabaseCount('business_drive_states', $expected, 'business');
    }

    public function test_sync_marks_has_files_true_when_files_present(): void
    {
        $this->mock(GoogleDriveService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('findOrCreateFolder')->andReturn('subfolder_id');
            $mock->shouldReceive('listFilesInFolder')->andReturn([['id' => 'file1', 'name' => 'doc.pdf']]);
        });

        app(BusinessDriveService::class)->syncSubfolderStates($this->business);

        $this->assertDatabaseHas('business_drive_states', [
            'business_id' => $this->business->id,
            'subfolder_name' => 'მიმოწერა',
            'has_files' => true,
        ], 'business');
    }

    public function test_sync_marks_has_files_false_when_folder_empty(): void
    {
        $this->mock(GoogleDriveService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('findOrCreateFolder')->andReturn('subfolder_id');
            $mock->shouldReceive('listFilesInFolder')->andReturn([]);
        });

        app(BusinessDriveService::class)->syncSubfolderStates($this->business);

        $state = BusinessDriveState::on('business')
            ->where('business_id', $this->business->id)
            ->where('subfolder_name', 'ფინანსური ანგარიშგება')
            ->first();

        $this->assertNotNull($state);
        $this->assertFalse($state->has_files);
    }

    public function test_sync_is_idempotent_on_second_call(): void
    {
        $subfolders = config('vigil.business.drive_subfolders');
        $expected = count($subfolders) + 1;

        $this->mock(GoogleDriveService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('findOrCreateFolder')->andReturn('subfolder_id');
            $mock->shouldReceive('listFilesInFolder')->andReturn([]);
        });

        $service = app(BusinessDriveService::class);
        $service->syncSubfolderStates($this->business);
        $service->syncSubfolderStates($this->business);

        $this->assertDatabaseCount('business_drive_states', $expected, 'business');
    }

    public function test_sync_skips_business_without_drive_folder(): void
    {
        $this->business->update(['drive_folder_url' => null]);

        $this->mock(GoogleDriveService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('findOrCreateFolder')->never();
            $mock->shouldReceive('listFilesInFolder')->never();
        });

        app(BusinessDriveService::class)->syncSubfolderStates($this->business);

        $this->assertDatabaseCount('business_drive_states', 0, 'business');
    }

    public function test_synced_at_is_set_on_each_row(): void
    {
        $this->mock(GoogleDriveService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('findOrCreateFolder')->andReturn('subfolder_id');
            $mock->shouldReceive('listFilesInFolder')->andReturn([]);
        });

        $before = now()->subSecond();
        app(BusinessDriveService::class)->syncSubfolderStates($this->business);

        $state = BusinessDriveState::on('business')
            ->where('business_id', $this->business->id)
            ->first();

        $this->assertNotNull($state->synced_at);
        $this->assertTrue($state->synced_at->isAfter($before));
    }
}
