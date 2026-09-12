<?php

use App\Enums\CreatorVettingStatus;
use App\Models\User;

test('creator onboarding starts on the linkedin step', function () {
    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->get(route('onboarding.creator'))
        ->assertOk()
        ->assertSee('Public LinkedIn URL');
});

test('creator onboarding blocks skipping to the offer', function () {
    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->post(route('onboarding.creator.offer'), ['price' => 240]);

    expect($user->fresh()->creatorProfile->price_cents)->toBeNull();
});

test('creator can complete onboarding steps', function () {
    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->post(route('onboarding.creator.linkedin'), [
            'linkedin_url' => 'https://www.linkedin.com/in/ada',
            'headline' => 'B2B writer for SaaS',
            'country' => 'FR',
        ])
        ->assertRedirect(route('onboarding.creator'));

    $this->actingAs($user)
        ->post(route('onboarding.creator.industries'), [
            'industries' => ['SaaS', 'AI'],
        ])
        ->assertRedirect(route('onboarding.creator'));

    $this->actingAs($user)
        ->post(route('onboarding.creator.offer'), [
            'price' => 240,
            'bundles' => [
                ['posts' => 5, 'total' => 1020],
            ],
        ])
        ->assertRedirect(route('onboarding.creator'));

    $this->actingAs($user)
        ->post(route('onboarding.creator.complete'))
        ->assertRedirect(route('creator'));

    $profile = $user->fresh()->creatorProfile;

    expect($profile->linkedin_url)->toBe('https://www.linkedin.com/in/ada')
        ->and($profile->headline)->toBe('B2B writer for SaaS')
        ->and($profile->country)->toBe('FR')
        ->and($profile->industries)->toBe(['SaaS', 'AI'])
        ->and($profile->price_cents)->toBe(24000)
        ->and($profile->bundles)->toBe([['posts' => 5, 'total_cents' => 102000]])
        ->and($profile->onboarded_at)->not->toBeNull()
        ->and($profile->vetting_status)->toBe(CreatorVettingStatus::Vetted);
});

test('creator linkedin url must be a public profile', function () {
    $user = User::factory()->creator()->create();

    $this->actingAs($user)
        ->from(route('onboarding.creator'))
        ->post(route('onboarding.creator.linkedin'), [
            'linkedin_url' => 'https://example.com/ada',
            'headline' => 'Writer',
            'country' => 'FR',
        ])
        ->assertRedirect(route('onboarding.creator'))
        ->assertSessionHasErrors('linkedin_url');
});
