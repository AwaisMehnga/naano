<?php

use App\Enums\CampaignStatus;
use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\User;

test('vetted creators can list and apply to an active campaign', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Active,
        'name' => 'Open brief',
        'brief' => ['context' => 'Ship a LinkedIn post.'],
    ]);
    $creator = marketplaceCreator();

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $campaign->id)
        ->assertJsonPath('data.0.name', 'Open brief');

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.show', $campaign))
        ->assertOk()
        ->assertJsonPath('data.brief.context', 'Ship a LinkedIn post.');

    $this->actingAs($creator->user)
        ->postJson(route('api.creator.opportunities.apply', $campaign))
        ->assertOk()
        ->assertJsonPath('data.status', 'applied')
        ->assertJsonPath('data.source', 'apply');
});

test('a creator cannot apply twice to the same campaign', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Active,
    ]);
    $creator = marketplaceCreator();

    $this->actingAs($creator->user)
        ->postJson(route('api.creator.opportunities.apply', $campaign))
        ->assertOk();

    $this->actingAs($creator->user)
        ->postJson(route('api.creator.opportunities.apply', $campaign))
        ->assertNotFound();
});

test('draft campaigns are not opportunities', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Draft,
    ]);
    $creator = marketplaceCreator();

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.show', $campaign))
        ->assertNotFound();
});

test('accepting an invite selects the creator without booking', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Active,
    ]);
    $creator = marketplaceCreator();
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'source' => CollaborationSource::Invite,
        'status' => CollaborationStatus::Invited,
    ]);

    $this->actingAs($creator->user)
        ->postJson(route('api.creator.collaborations.accept', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.status', 'selected');

    expect($collaboration->fresh()->status)->toBe(CollaborationStatus::Selected)
        ->and($collaboration->fresh()->accepted_at)->not->toBeNull()
        ->and($collaboration->fresh()->booked_at)->toBeNull();
});

test('creators can decline an invite', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Invited,
    ]);

    $this->actingAs($creator->user)
        ->postJson(route('api.creator.collaborations.decline', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.status', 'declined');
});

test('a creator cannot see another creators deal', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'status' => CollaborationStatus::Invited,
    ]);
    $other = marketplaceCreator(['display_name' => 'Other']);

    $this->actingAs($other->user)
        ->getJson(route('api.creator.collaborations.show', $collaboration))
        ->assertNotFound();
});
