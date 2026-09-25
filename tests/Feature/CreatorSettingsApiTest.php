<?php

use App\Models\CreatorAudienceProfile;
use App\Models\User;
use App\Services\Stripe\FakeStripeGateway;
use App\Services\Stripe\StripeGateway;

test('creators can read audience and refresh a snapshot', function () {
    $user = User::factory()->creator()->onboarded()->create();
    $user->creatorProfile->update([
        'linkedin_profile' => [
            'follower_count' => 1200,
            'connections_count' => 800,
            'engagers' => [
                'people_count' => 2,
                'reply_rate' => 1.0,
                'seniority' => [
                    ['label' => 'Founder / C-level', 'count' => 2],
                ],
                'job_title' => [
                    ['label' => 'Founders', 'count' => 2],
                ],
                'locations' => [
                    ['label' => 'Paris', 'count' => 2],
                ],
                'top' => [],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->getJson(route('api.creator.audience.show'))
        ->assertOk()
        ->assertJsonPath('data.followers_count', 1200)
        ->assertJsonPath('data.connections_count', 800);

    $this->actingAs($user)
        ->postJson(route('api.creator.audience.store'))
        ->assertOk()
        ->assertJsonPath('data.followers_count', 1200)
        ->assertJsonPath('data.connections_count', 800)
        ->assertJsonPath('data.audience_mix.seniority.Founder / C-level', 100)
        ->assertJsonPath('data.audience_mix.geo.Paris', 100);

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

test('connect onboarding rejects a missing country', function () {
    $user = User::factory()->creator()->onboarded()->create();
    $user->creatorProfile->update(['country' => null]);

    $this->actingAs($user)
        ->postJson(route('api.creator.connect.onboarding'))
        ->assertUnprocessable()
        ->assertJsonPath('data.country.0', 'Add your country on your profile before setting up payouts.');
});

test('connect onboarding recreates the account when the profile country changed', function () {
    $user = User::factory()->creator()->onboarded()->create();
    $user->creatorProfile->update([
        'country' => 'DE',
        'stripe_connect_id' => 'acct_old',
        'payouts_enabled' => true,
    ]);

    $fake = new FakeStripeGateway;
    $fake->accountCountries['acct_old'] = 'FR';
    $this->app->instance(StripeGateway::class, $fake);

    $this->actingAs($user)
        ->postJson(route('api.creator.connect.onboarding'))
        ->assertOk();

    $profile = $user->creatorProfile->fresh();

    expect($profile->stripe_connect_id)->toBe('acct_fake_'.$profile->id)
        ->and($profile->payouts_enabled)->toBeFalse();
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
