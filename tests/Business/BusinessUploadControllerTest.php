<?php

declare(strict_types=1);

namespace Tests\Business;

use App\Business\Models\Business;
use App\Business\Models\User;
use App\Business\Services\BusinessDriveService;
use App\Services\GoogleDriveService;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;

class BusinessUploadControllerTest extends BusinessTestCase
{
    // ------------------------------------------------------------------ folders

    public function test_folders_returns_error_when_business_has_no_drive_folder(): void
    {
        $user = User::factory()->create();

        $business = Business::factory()->create([
            'drive_folder_url' => null,
        ]);

        $this->actingAs($user, 'business')
            ->getJson(route('business.upload.folders', ['business_id' => $business->id]))
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'ბიზნესს Drive საქაღალდე არ აქვს']);
    }

    public function test_folders_returns_subfolder_list_excluding_mimotsera(): void
    {
        $user = User::factory()->create();

        $business = Business::factory()->create([
            'drive_folder_url' => 'https://drive.google.com/drive/folders/FAKE_ID',
        ]);

        $this->mock(GoogleDriveService::class, function (MockInterface $mock) use ($business): void {
            $mock->shouldReceive('listSubfoldersFromUrl')
                ->once()
                ->with($business->drive_folder_url, ['მიმოწერა'])
                ->andReturn(['დოკუმენტაცია', 'ხელშეკრულება']);
        });

        $this->actingAs($user, 'business')
            ->getJson(route('business.upload.folders', ['business_id' => $business->id]))
            ->assertOk()
            ->assertJsonFragment(['folders' => ['დოკუმენტაცია', 'ხელშეკრულება']])
            ->assertJsonFragment(['code' => $business->registration_code])
            ->assertJsonFragment(['name' => $business->name]);
    }

    // ------------------------------------------------------------------ upload

    public function test_upload_stores_files_in_chosen_subfolder(): void
    {
        $user = User::factory()->create();

        $business = Business::factory()->create([
            'drive_folder_url' => 'https://drive.google.com/drive/folders/FAKE_ID',
        ]);

        $this->mock(GoogleDriveService::class, function (MockInterface $mock) use ($business): void {
            $mock->shouldReceive('findOrCreateSubfolder')
                ->once()
                ->with($business->drive_folder_url, 'დოკუმენტაცია')
                ->andReturn('SUBFOLDER_ID');
        });

        $this->mock(BusinessDriveService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('uploadFileToDrive')
                ->once()
                ->withArgs(function (string $folderId, string $name): bool {
                    return $folderId === 'SUBFOLDER_ID' && $name === 'test.pdf';
                });
        });

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->actingAs($user, 'business')
            ->postJson(route('business.upload.store'), [
                'business_id' => $business->id,
                'folder' => 'დოკუმენტაცია',
                'files' => [$file],
            ])
            ->assertOk()
            ->assertJsonFragment(['uploaded' => ['test.pdf']]);
    }

    public function test_upload_returns_error_when_business_has_no_drive_folder(): void
    {
        $user = User::factory()->create();

        $business = Business::factory()->create([
            'drive_folder_url' => null,
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->actingAs($user, 'business')
            ->postJson(route('business.upload.store'), [
                'business_id' => $business->id,
                'folder' => 'დოკუმენტაცია',
                'files' => [$file],
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'ბიზნესს Drive საქაღალდე არ აქვს']);
    }
}
