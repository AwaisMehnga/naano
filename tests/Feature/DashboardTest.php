<?php

use App\Models\User;

test('guests are redirected to the login page from company and creator', function (string $path) {
    $this->get($path)->assertRedirect(route('login'));
})->with(['/company', '/company/anything', '/creator', '/creator/anything', '/dashboard']);

test('onboarded company users can visit the company dashboard', function () {
    $user = User::factory()->company()->onboarded()->create();

    $this->actingAs($user)
        ->get('/company')
        ->assertOk();
});

test('onboarded creator users can visit the creator dashboard', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->get('/creator')
        ->assertOk();
});

test('dashboard redirects onboarded company users to the company spa', function () {
    $user = User::factory()->company()->onboarded()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('company', absolute: false));
});

test('dashboard redirects onboarded creator users to the creator spa', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('creator', absolute: false));
});

test('incomplete creator onboarding is sent back to onboarding from the creator spa', function () {
    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->get('/creator')
        ->assertRedirect(route('onboarding.creator'));
});
