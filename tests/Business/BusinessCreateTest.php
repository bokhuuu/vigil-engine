<?php

declare(strict_types=1);

namespace Tests\Business;

use App\Business\Models\User;
use App\Business\Services\BusinessDriveService;
use Mockery\MockInterface;

class BusinessCreateTest extends BusinessTestCase
{
    public function test_it_auto_creates_drive_folder_when_no_url_submitted(): void
    {
        $user = User::factory()->create();

        $this->mock(BusinessDriveService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createBusinessFolder')->once();
        });

        $this->actingAs($user, 'business')
            ->post(route('business.store'), [
                'name' => 'Test Business',
                'registration_code' => '123456789',
                'lot_number' => 1,
                'type' => 'მეპატრონე',
                'status' => 'active',
            ])->assertRedirect();

        $this->assertDatabaseHas('businesses', [
            'registration_code' => '123456789',
        ], 'business');
    }

    public function test_it_skips_drive_folder_when_url_provided(): void
    {
        $this->mock(BusinessDriveService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createBusinessFolder')->never();
        });

        $this->post(route('business.store'), [
            'name' => 'Test Business',
            'registration_code' => '987654321',
            'lot_number' => 1,
            'type' => 'მეპატრონე',
            'status' => 'active',
            'drive_folder_url' => 'https://drive.google.com/drive/folders/abc123',
        ])->assertRedirect();
    }
}
