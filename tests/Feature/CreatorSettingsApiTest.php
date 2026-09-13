<?php

use App\Models\CreatorAudienceProfile;
use App\Models\User;

test('creators can read audience and refresh a snapshot', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->getJson(route('api.creator.audience.show'))
        ->assertOk()
        ->assertJsonPath('data.followers_count', null);

    $this->actingAs($user)
        ->postJson(route('api.creator.audience.store'))
        ->assertOk();

    expect(CreatorAudienceProfile::query()->where('creator_profile_id', $user->creatorProfile->id)->count())->toBe(1);

    $this->actingAs($user)
        ->getJson(route('api.creator.billing.show'))
        ->assertOk()
        ->assertJsonPath('data.payouts_enabled', false)
        ->assertJsonPath('data.bank_summary', null);
});

test('creators can delete their account with the current password', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->deleteJson(route('api.creator.account.destroy'), [
            'password' => 'password',
        ])
        ->assertOk();

    $this->assertGuest();
    expect(User::query()->where('id', $user->id)->exists())->toBeFalse();
});

test('connect onboarding returns a setup url', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->postJson(route('api.creator.connect.onboarding'))
        ->assertOk()
        ->assertJsonPath('data.url', 'https://connect.stripe.test/setup/acct_fake_'.$user->creatorProfile->id);

    expect($user->creatorProfile->fresh()->stripe_connect_id)->toBe('acct_fake_'.$user->creatorProfile->id);
});

test('account delete rejects a wrong password', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->deleteJson(route('api.creator.account.destroy'), [
            'password' => 'nope',
        ])
        ->assertUnprocessable();

    expect(User::query()->where('id', $user->id)->exists())->toBeTrue();
});
