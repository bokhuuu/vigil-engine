<?php

declare(strict_types=1);

namespace Tests\Business;

use App\Business\Models\Business;
use App\Business\Models\Correspondence;
use App\Business\Models\User;

class CorrespondenceControllerTest extends BusinessTestCase
{
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user, 'business');

        $this->business = Business::factory()->create([
            'owner_email' => 'owner@example.com',
            'accountant_email' => 'acc@example.com',
            'cadastral_code' => '05.19.22.026',
            'status' => 'active',
        ]);
    }

    public function test_store_sets_business_status_to_mimdianare(): void
    {
        $this->post(route('business.correspondence.store', $this->business))
            ->assertRedirect(route('business.show', $this->business))
            ->assertSessionHas('success');

        $this->assertSame('მიმდინარე', $this->business->fresh()->status);
    }

    public function test_store_does_not_create_correspondence_record(): void
    {
        $this->post(route('business.correspondence.store', $this->business));

        $this->assertSame(0, Correspondence::count());
    }

    public function test_destroy_deletes_correspondence(): void
    {
        $corr = Correspondence::factory()->create(['business_id' => $this->business->id, 'round' => 1]);

        $this->delete(route('business.correspondence.destroy', [$this->business, $corr]))
            ->assertRedirect(route('business.show', $this->business))
            ->assertSessionHas('success');

        $this->assertNull(Correspondence::find($corr->id));
    }
}
