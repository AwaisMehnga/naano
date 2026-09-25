<?php

use App\Models\User;

test('guests receive an ajax error from api user', function () {
    $this->getJson(route('api.user'))
        ->assertUnauthorized()
        ->assertJson([
            'status' => 'error',
            'message' => 'Unauthenticated.',
            'data' => [],
        ]);
});

test('authenticated users receive ajax success from api user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.user'))
        ->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'OK',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => null,
                'role' => null,
                'active_profile' => null,
                'profiles' => [],
                'can_create_profiles' => ['creator', 'company'],
                'onboarded' => false,
            ],
        ]);
});

test('onboarded company users receive active profile context on api user', function () {
    $user = User::factory()->company()->onboarded()->create();

    $this->actingAs($user)
        ->getJson(route('api.user'))
        ->assertOk()
        ->assertJsonPath('data.role', 'company')
        ->assertJsonPath('data.active_profile', 'company')
        ->assertJsonPath('data.onboarded', true)
        ->assertJsonPath('data.profiles.0.type', 'company');
});

test('guests cannot call company profile', function () {
    $this->getJson(route('api.company.profile.show'))
        ->assertUnauthorized();
});
