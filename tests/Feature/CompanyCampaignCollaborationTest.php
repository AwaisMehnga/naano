<?php

use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Enums\CreatorVettingStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\User;

test('company users can invite a vetted creator onto a campaign', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator(['display_name' => 'Invited Ada']);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.invites.store', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'invited')
        ->assertJsonPath('data.source', 'invite')
        ->assertJsonPath('data.creator.id', $creator->id)
        ->assertJsonPath('data.creator.display_name', 'Invited Ada');

    $this->assertDatabaseHas('collaborations', [
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Invited->value,
        'invited_by_user_id' => $owner->id,
    ]);
});

test('pending creators cannot be invited', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator([
        'vetting_status' => CreatorVettingStatus::Pending,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.invites.store', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('data.creator_profile_id.0', 'This creator cannot be invited.');
});

test('the same creator cannot be invited twice', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();

    Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Invited,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.invites.store', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('data.creator_profile_id.0', 'This creator is already on the campaign.');
});

test('company users cannot invite onto another workspace campaign', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $other = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $other->company->id,
        'created_by_user_id' => $other->id,
    ]);
    $creator = marketplaceCreator();

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.invites.store', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertNotFound();
});

test('pipeline filters collaborations on a campaign', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);

    $invited = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'source' => CollaborationSource::Invite,
        'status' => CollaborationStatus::Invited,
    ]);
    $applied = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'source' => CollaborationSource::Apply,
        'status' => CollaborationStatus::Applied,
    ]);
    Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'status' => CollaborationStatus::Booked,
    ]);
    Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'status' => CollaborationStatus::Cancelled,
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.collaborations.index', [
            'campaign' => $campaign,
            'pipeline' => 'invitations_sent',
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $invited->id);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.collaborations.index', [
            'campaign' => $campaign,
            'pipeline' => 'invitations_received',
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $applied->id);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.collaborations.index', [
            'campaign' => $campaign,
            'pipeline' => 'all',
        ]))
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('company users can select an invited collaboration', function () {
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

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.select', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.status', 'selected');
});

test('booked collaborations cannot be selected', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'status' => CollaborationStatus::Booked,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.select', $collaboration))
        ->assertUnprocessable();
});

test('company users can cancel a collaboration', function () {
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

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.cancel', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

test('company users cannot select another workspace collaboration', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $other = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $other->company->id,
        'created_by_user_id' => $other->id,
    ]);
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'status' => CollaborationStatus::Invited,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.select', $collaboration))
        ->assertNotFound();
});

test('guests and creators cannot list campaign collaborations', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = User::factory()->creator()->onboarded()->create();

    $this->getJson(route('api.company.campaigns.collaborations.index', $campaign))
        ->assertUnauthorized();

    $this->actingAs($creator)
        ->getJson(route('api.company.campaigns.collaborations.index', $campaign))
        ->assertForbidden();
});

test('company collaboration index paginates and filters by pipeline and campaign', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaignA = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'name' => 'Alpha',
    ]);
    $campaignB = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'name' => 'Beta',
    ]);

    Collaboration::factory()->create([
        'campaign_id' => $campaignA->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'status' => CollaborationStatus::Invited,
    ]);
    Collaboration::factory()->create([
        'campaign_id' => $campaignA->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'status' => CollaborationStatus::Booked,
    ]);
    Collaboration::factory()->create([
        'campaign_id' => $campaignB->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'status' => CollaborationStatus::Applied,
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.collaborations.index', [
            'pipeline' => 'invitations_sent',
            'campaign_id' => $campaignA->id,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.status', 'invited')
        ->assertJsonPath('data.counts.all', 2)
        ->assertJsonPath('data.counts.invitations_sent', 1)
        ->assertJsonPath('data.counts.active', 1);

    $this->actingAs($owner)
        ->getJson(route('api.company.collaborations.index', [
            'per_page' => 1,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertJsonPath('data.per_page', 1)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.total', 3);
});
