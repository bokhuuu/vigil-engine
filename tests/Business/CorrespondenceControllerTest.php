<?php

declare(strict_types=1);

namespace Tests\Business;

use App\Business\Models\Business;
use App\Business\Models\Correspondence;
use App\Business\Models\User;
use App\Business\Services\BusinessDriveService;

class CorrespondenceControllerTest extends BusinessTestCase
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
                $mock->shouldReceive('createCorrespondenceRoundFolders')->andReturn('https://drive.google.com/drive/folders/fake');
            }),
        );

        $this->business = Business::factory()->create([
            'owner_email' => 'owner@example.com',
            'accountant_email' => 'acc@example.com',
            'cadastral_code' => '05.19.22.026',
        ]);
    }

    public function test_stores_correspondence_with_only_dates(): void
    {
        $this->post(route('business.correspondence.store', $this->business), [
            'sent_at' => '2026-10-25',
            'deadline_at' => '2026-11-04',
        ])
            ->assertRedirect(route('business.show', $this->business))
            ->assertSessionHas('success');

        $corr = Correspondence::first();
        $this->assertSame(1, $corr->round);
        $this->assertSame('pending', $corr->status);
        $this->assertNull($corr->recipient_email);
        $this->assertNull($corr->cc_emails);
        $this->assertNull($corr->subject);
        $this->assertNull($corr->body);
    }

    public function test_stores_correspondence_with_all_draft_fields(): void
    {
        $cc = ['ramaz@colliers.ge', 'levan@colliers.ge', 'engine@gmail.com'];

        $this->post(route('business.correspondence.store', $this->business), [
            'sent_at' => '2026-10-25',
            'deadline_at' => '2026-11-04',
            'recipient_email' => 'owner@example.com, acc@example.com',
            'cc_emails' => json_encode($cc),
            'subject' => 'შპს მაგალითი / კოდი: 205123456',
            'body' => 'მოგესალმებით, ...',
        ])
            ->assertRedirect(route('business.show', $this->business))
            ->assertSessionHas('success');

        $corr = Correspondence::first();
        $this->assertSame('owner@example.com, acc@example.com', $corr->recipient_email);
        $this->assertSame($cc, $corr->cc_emails);
        $this->assertSame('შპს მაგალითი / კოდი: 205123456', $corr->subject);
        $this->assertSame('მოგესალმებით, ...', $corr->body);
    }

    public function test_increments_round_on_second_correspondence(): void
    {
        Correspondence::factory()->create(['business_id' => $this->business->id, 'round' => 1]);

        $this->post(route('business.correspondence.store', $this->business), [
            'sent_at' => '2026-10-25',
            'deadline_at' => '2026-11-04',
        ])->assertRedirect();

        $this->assertSame(2, (int) Correspondence::max('round'));
    }

    public function test_invalid_cc_emails_json_stores_null(): void
    {
        $this->post(route('business.correspondence.store', $this->business), [
            'sent_at' => '2026-10-25',
            'deadline_at' => '2026-11-04',
            'cc_emails' => 'not-json',
        ])->assertRedirect();

        $this->assertNull(Correspondence::first()->cc_emails);
    }

    public function test_store_calls_drive_folder_creation(): void
    {
        $mock = \Mockery::mock(BusinessDriveService::class);
        $mock->shouldReceive('createCorrespondenceRoundFolders')
            ->once()
            ->with(\Mockery::type(Business::class), 1)
            ->andReturn('https://drive.google.com/drive/folders/fake');

        $this->app->instance(BusinessDriveService::class, $mock);

        $this->post(route('business.correspondence.store', $this->business), [
            'sent_at' => '2026-10-25',
            'deadline_at' => '2026-11-04',
        ])->assertRedirect();
    }
}
