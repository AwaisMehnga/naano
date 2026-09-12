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
                'onboarded' => false,
                'current_company_id' => null,
                'membership_role' => null,
            ],
        ]);
});

test('onboarded company users receive workspace context on api user', function () {
    $user = User::factory()->company()->onboarded()->create();
    $company = $user->companies()->first();

    $this->actingAs($user)
        ->getJson(route('api.user'))
        ->assertOk()
        ->assertJsonPath('data.role', 'company')
        ->assertJsonPath('data.onboarded', true)
        ->assertJsonPath('data.current_company_id', $company->id)
        ->assertJsonPath('data.membership_role', 'owner');
});

test('guests cannot call company profile', function () {
    $this->getJson(route('api.company.profile.show'))
        ->assertUnauthorized();
});
