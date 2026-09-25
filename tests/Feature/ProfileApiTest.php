<?php

use App\Enums\ProfileType;
use App\Models\User;

test('users can list create and switch profiles', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.profiles.index'))
        ->assertOk()
        ->assertJsonPath('data.profiles', [])
        ->assertJsonPath('data.active_profile', null)
        ->assertJsonPath('data.can_create', ['creator', 'company']);

    $this->actingAs($user)
        ->postJson(route('api.profiles.creator.store'))
        ->assertOk()
        ->assertJsonPath('data.active_profile', 'creator')
        ->assertJsonPath('data.can_create', ['company']);

    expect($user->fresh()->creatorProfile)->not->toBeNull();

    $this->actingAs($user)
        ->postJson(route('api.profiles.company.store'))
        ->assertOk()
        ->assertJsonPath('data.active_profile', 'company')
        ->assertJsonPath('data.can_create', []);

    expect($user->fresh()->company)->not->toBeNull();

    $this->actingAs($user)
        ->patchJson(route('api.profiles.active.update'), ['type' => ProfileType::Creator->value])
        ->assertOk()
        ->assertJsonPath('data.active_profile', 'creator');
});

test('users cannot create a duplicate profile type', function () {
    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->postJson(route('api.profiles.creator.store'))
        ->assertUnprocessable();
});
