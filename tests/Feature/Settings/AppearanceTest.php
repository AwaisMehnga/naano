<?php

use App\Models\User;

test('appearance page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertViewIs('settings.appearance');
});

test('appearance can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('appearance.update'), [
            'appearance' => 'dark',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('appearance.edit'))
        ->assertPlainCookie('appearance', 'dark');
});

test('appearance must be a supported value', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('appearance.edit'))
        ->put(route('appearance.update'), [
            'appearance' => 'neon',
        ])
        ->assertSessionHasErrors('appearance')
        ->assertRedirect(route('appearance.edit'));
});
