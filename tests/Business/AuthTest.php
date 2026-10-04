<?php

declare(strict_types=1);

use App\Business\Models\User;

it('shows the business login page', function () {
    $this->get(route('business.login'))
        ->assertStatus(200)
        ->assertSee('შესვლა');
});

it('rejects wrong credentials', function () {
    $this->post(route('business.login'), [
        'email' => 'wrong@example.com',
        'password' => 'wrongpassword',
    ])->assertSessionHasErrors('email');
});

it('redirects to login when not authenticated', function () {
    $this->get(route('business.dashboard'))
        ->assertRedirect(route('business.login'));
});

it('logs out and redirects to login', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'business')
        ->post(route('business.logout'))
        ->assertRedirect(route('business.login'));
});
