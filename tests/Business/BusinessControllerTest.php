<?php

declare(strict_types=1);

namespace Tests\Business;

use App\Business\Models\Business;
use App\Business\Models\User;
use App\Business\Services\BusinessDriveService;

class BusinessControllerTest extends BusinessTestCase
{
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user, 'business');

        $this->app->instance(
            BusinessDriveService::class,
            \Mockery::mock(BusinessDriveService::class, function ($mock) {
                $mock->shouldReceive('getFolderIdFromUrl')->andReturn('fake-folder-id');
                $mock->shouldReceive('renameBusinessFolder')->andReturn(null);
            }),
        );

        $this->business = Business::factory()->create([
            'name' => 'შპს მაგალითი',
            'registration_code' => '205123456',
            'drive_folder_url' => 'https://drive.google.com/drive/folders/fake-folder-id',
        ]);
    }

    public function test_update_renames_drive_folder_when_name_changes(): void
    {
        $mock = \Mockery::mock(BusinessDriveService::class);
        $mock->shouldReceive('getFolderIdFromUrl')
            ->once()
            ->with($this->business->drive_folder_url)
            ->andReturn('fake-folder-id');
        $mock->shouldReceive('renameBusinessFolder')
            ->once()
            ->with('fake-folder-id', 'შპს ახალი (205123456)');

        $this->app->instance(BusinessDriveService::class, $mock);

        $this->put(route('business.update', $this->business), [
            'name' => 'შპს ახალი',
            'registration_code' => '205123456',
            'lot_number' => 2,
            'type' => ['მეპატრონე'],
            'status' => 'active',
        ])->assertRedirect(route('business.show', $this->business));
    }

    public function test_update_renames_drive_folder_when_code_changes(): void
    {
        $mock = \Mockery::mock(BusinessDriveService::class);
        $mock->shouldReceive('getFolderIdFromUrl')->once()->andReturn('fake-folder-id');
        $mock->shouldReceive('renameBusinessFolder')
            ->once()
            ->with('fake-folder-id', 'შპს მაგალითი (999999999)');

        $this->app->instance(BusinessDriveService::class, $mock);

        $this->put(route('business.update', $this->business), [
            'name' => 'შპს მაგალითი',
            'registration_code' => '999999999',
            'lot_number' => 2,
            'type' => ['მეპატრონე'],
            'status' => 'active',
        ])->assertRedirect();
    }

    public function test_update_does_not_rename_when_name_and_code_unchanged(): void
    {
        $mock = \Mockery::mock(BusinessDriveService::class);
        $mock->shouldNotReceive('renameBusinessFolder');
        $mock->shouldNotReceive('getFolderIdFromUrl');

        $this->app->instance(BusinessDriveService::class, $mock);

        $this->put(route('business.update', $this->business), [
            'name' => 'შპს მაგალითი',
            'registration_code' => '205123456',
            'lot_number' => 2,
            'type' => ['მეპატრონე'],
            'status' => 'active',
        ])->assertRedirect();
    }

    public function test_destroy_unlinks_drive_folder_before_delete(): void
    {
        $this->delete(route('business.destroy', $this->business))
            ->assertRedirect(route('business.dashboard'));

        $this->assertNull($this->business->fresh()?->drive_folder_url);
        $this->assertNull(Business::find($this->business->id));
    }
}
