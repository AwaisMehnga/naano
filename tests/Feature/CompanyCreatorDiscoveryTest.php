<?php

use App\Enums\CreatorVettingStatus;
use App\Models\CreatorAudienceProfile;
use App\Models\CreatorOffer;
use App\Models\Niche;
use App\Models\User;

test('company users list vetted onboarded creators and hide pending ones', function () {
    $company = User::factory()->company()->onboarded()->create();
    $visible = marketplaceCreator(['display_name' => 'Visible Ada']);
    marketplaceCreator([
        'display_name' => 'Hidden Ada',
        'vetting_status' => CreatorVettingStatus::Pending,
    ]);

    $this->actingAs($company)
        ->getJson(route('api.company.creators.index'))
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.id', $visible->id)
        ->assertJsonPath('data.items.0.display_name', 'Visible Ada')
        ->assertJsonPath('data.items.0.from_price_cents', 24000);
});

test('company users can filter creators by niche country price followers and query', function () {
    $company = User::factory()->company()->onboarded()->create();
    $saas = Niche::factory()->create(['name' => 'SaaS', 'slug' => 'saas']);
    $hr = Niche::factory()->create(['name' => 'HR', 'slug' => 'hr']);

    $match = marketplaceCreator([
        'display_name' => 'Fit Creator',
        'headline' => 'Pipeline posts',
        'bio' => 'SaaS demand gen',
        'country' => 'DE',
        'price_cents' => 20000,
    ]);
    $match->niches()->attach($saas->id);
    CreatorAudienceProfile::factory()->create([
        'creator_profile_id' => $match->id,
        'followers_count' => 8000,
    ]);

    $other = marketplaceCreator([
        'display_name' => 'Other Creator',
        'country' => 'FR',
        'price_cents' => 50000,
    ]);
    $other->niches()->attach($hr->id);
    CreatorAudienceProfile::factory()->create([
        'creator_profile_id' => $other->id,
        'followers_count' => 500,
    ]);
    CreatorOffer::factory()->create([
        'creator_profile_id' => $other->id,
        'price_cents' => 90000,
        'is_active' => true,
    ]);

    $this->actingAs($company)
        ->getJson(route('api.company.creators.index', [
            'q' => 'Pipeline',
            'niche_id' => $saas->id,
            'country' => 'DE',
            'min_price_cents' => 15000,
            'max_price_cents' => 30000,
            'min_followers' => 1000,
            'max_followers' => 20000,
        ]))
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.id', $match->id);
});

test('listed price prefers the cheapest active offer over the profile rate', function () {
    $company = User::factory()->company()->onboarded()->create();
    $creator = marketplaceCreator(['price_cents' => 40000]);
    CreatorOffer::factory()->create([
        'creator_profile_id' => $creator->id,
        'price_cents' => 18000,
        'is_active' => true,
    ]);
    CreatorOffer::factory()->create([
        'creator_profile_id' => $creator->id,
        'price_cents' => 22000,
        'is_active' => false,
    ]);

    $this->actingAs($company)
        ->getJson(route('api.company.creators.index'))
        ->assertOk()
        ->assertJsonPath('data.items.0.from_price_cents', 18000);
});

test('company users can view a vetted creator card', function () {
    $company = User::factory()->company()->onboarded()->create();
    $creator = marketplaceCreator([
        'display_name' => 'Ada',
        'bio' => 'Writes for SaaS',
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
    ]);
    $niche = Niche::factory()->create(['name' => 'SaaS', 'slug' => 'saas']);
    $creator->niches()->attach($niche->id);
    CreatorAudienceProfile::factory()->create([
        'creator_profile_id' => $creator->id,
        'followers_count' => 12000,
        'audience_mix' => ['geo' => ['DE' => 100]],
    ]);
    CreatorOffer::factory()->create([
        'creator_profile_id' => $creator->id,
        'price_cents' => 21000,
        'is_active' => true,
    ]);

    $this->actingAs($company)
        ->getJson(route('api.company.creators.show', $creator))
        ->assertOk()
        ->assertJsonPath('data.id', $creator->id)
        ->assertJsonPath('data.bio', 'Writes for SaaS')
        ->assertJsonPath('data.followers_count', 12000)
        ->assertJsonPath('data.from_price_cents', 21000)
        ->assertJsonPath('data.offers.0.price_cents', 21000)
        ->assertJsonPath('data.recent_metrics', []);
});

test('pending creators return 404 on the marketplace card', function () {
    $company = User::factory()->company()->onboarded()->create();
    $creator = marketplaceCreator([
        'vetting_status' => CreatorVettingStatus::Pending,
    ]);

    $this->actingAs($company)
        ->getJson(route('api.company.creators.show', $creator))
        ->assertNotFound();
});

test('guests and creators cannot browse the company marketplace', function () {
    $creator = User::factory()->creator()->onboarded()->create();

    $this->getJson(route('api.company.creators.index'))
        ->assertUnauthorized();

    $this->actingAs($creator)
        ->getJson(route('api.company.creators.index'))
        ->assertForbidden();
});
