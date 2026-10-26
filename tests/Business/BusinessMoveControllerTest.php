<?php

declare(strict_types=1);

namespace Tests\Business;

use App\Business\Models\Business;
use App\Business\Models\User;
use App\Business\Services\BusinessDriveService;

class BusinessMoveControllerTest extends BusinessTestCase
{
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user, 'business');

        $this->business = Business::factory()->create([
            'name' => 'შპს ტესტი',
            'registration_code' => '123456789',
            'drive_folder_url' => 'https://drive.google.com/drive/folders/fake-folder-id',
        ]);
    }

    public function test_files_returns_incoming_files_and_subfolders(): void
    {
        $mock = \Mockery::mock(BusinessDriveService::class);
        $mock->shouldReceive('listIncomingFiles')
            ->once()
            ->with(\Mockery::type(Business::class))
            ->andReturn([
                ['id' => 'file-1', 'name' => 'test.pdf', 'mimeType' => 'application/pdf', 'round' => 'მიმოწერა-1'],
            ]);
        $this->app->instance(BusinessDriveService::class, $mock);

        $this->getJson(route('business.move.files', $this->business))
            ->assertOk()
            ->assertJsonStructure(['files', 'subfolders'])
            ->assertJsonCount(1, 'files')
            ->assertJsonPath('files.0.id', 'file-1');
    }

    public function test_files_returns_empty_when_no_incoming_files(): void
    {
        $mock = \Mockery::mock(BusinessDriveService::class);
        $mock->shouldReceive('listIncomingFiles')->once()->andReturn([]);
        $this->app->instance(BusinessDriveService::class, $mock);

        $this->getJson(route('business.move.files', $this->business))
            ->assertOk()
            ->assertJsonCount(0, 'files');
    }

    public function test_move_calls_drive_service_and_returns_ok(): void
    {
        $mock = \Mockery::mock(BusinessDriveService::class);
        $mock->shouldReceive('moveFileToSubfolder')
            ->once()
            ->with(\Mockery::type(Business::class), 'file-abc', 'ფინანსური ანგარიშგება');
        $this->app->instance(BusinessDriveService::class, $mock);

        $this->postJson(route('business.move.store', $this->business), [
            'file_id' => 'file-abc',
            'subfolder_name' => 'ფინანსური ანგარიშგება',
        ])->assertOk()->assertJson(['ok' => true]);
    }

    public function test_move_validates_required_fields(): void
    {
        $this->app->instance(
            BusinessDriveService::class,
            \Mockery::mock(BusinessDriveService::class, function ($mock) {
                $mock->shouldNotReceive('moveFileToSubfolder');
            }),
        );

        $response = $this->postJson(route('business.move.store', $this->business), [
            'file_id' => '',
            'subfolder_name' => '',
        ]);
    }
}
