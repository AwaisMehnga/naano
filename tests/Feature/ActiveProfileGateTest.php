<?php

use App\Models\User;

test('creators without a company profile are redirected away from the company dashboard', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->get('/company')
        ->assertRedirect(route('profiles.choose'));
});

test('companies without a creator profile are redirected away from the creator dashboard', function () {
    $user = User::factory()->company()->onboarded()->create();

    $this->actingAs($user)
        ->get('/creator')
        ->assertRedirect(route('profiles.choose'));
});

test('creators cannot call company apis', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->getJson(route('api.company.ping'))
        ->assertForbidden()
        ->assertJson(['status' => 'error']);
});

test('companies cannot call creator apis', function () {
    $user = User::factory()->company()->onboarded()->create();

    $this->actingAs($user)
        ->getJson(route('api.creator.ping'))
        ->assertForbidden()
        ->assertJson(['status' => 'error']);
});

test('unverified creators cannot open onboarding', function () {
    $user = User::factory()->unverified()->creator()->create();

    $this->actingAs($user)
        ->get(route('onboarding.creator'))
        ->assertRedirect(route('verification.notice'));
});

test('dual profile users can open both dashboards after switching', function () {
    $user = User::factory()->creator()->company()->onboarded()->create();

    $this->actingAs($user)
        ->withHeaders(['X-Profile-Type' => 'company'])
        ->get('/company')
        ->assertOk();

    $this->actingAs($user)
        ->withHeaders(['X-Profile-Type' => 'creator'])
        ->get('/creator')
        ->assertOk();
});
