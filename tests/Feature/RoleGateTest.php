<?php

use App\Models\User;

test('creators cannot visit the company dashboard', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->get('/company')
        ->assertForbidden();
});

test('companies cannot visit the creator dashboard', function () {
    $user = User::factory()->company()->onboarded()->create();

    $this->actingAs($user)
        ->get('/creator')
        ->assertForbidden();
});

test('creators cannot call company apis', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->getJson(route('api.company.ping'))
        ->assertForbidden()
        ->assertJson(['status' => 'error']);
});

test('unverified creators cannot open onboarding', function () {
    $user = User::factory()->unverified()->creator()->create();

    $this->actingAs($user)
        ->get(route('onboarding.creator'))
        ->assertRedirect(route('verification.notice'));
});
