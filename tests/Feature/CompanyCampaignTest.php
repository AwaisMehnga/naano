<?php

use App\Enums\CampaignObjective;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\CollaborationStatus;
use App\Enums\LeadSource;
use App\Enums\PostStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\CompanyIcp;
use App\Models\Lead;
use App\Models\Post;
use App\Models\User;

test('company users can search their campaigns by name', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $match = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'name' => 'Q4 Pipeline',
    ]);
    Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'name' => 'Hiring brand',
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.index', ['q' => 'Pipeline']))
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.id', $match->id)
        ->assertJsonPath('data.items.0.name', 'Q4 Pipeline')
        ->assertJsonPath('data.total', 1);
});

test('company users do not see another workspace campaigns', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $other = User::factory()->company()->onboarded()->create();
    Campaign::factory()->create([
        'company_id' => $other->company->id,
        'created_by_user_id' => $other->id,
        'name' => 'Secret launch',
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.index', ['q' => 'Secret']))
        ->assertOk()
        ->assertJsonCount(0, 'data.items');
});

test('guests and creators cannot search company campaigns', function () {
    $creator = User::factory()->creator()->onboarded()->create();

    $this->getJson(route('api.company.campaigns.index'))
        ->assertUnauthorized();

    $this->actingAs($creator)
        ->getJson(route('api.company.campaigns.index'))
        ->assertForbidden();
});

test('company users can create a draft campaign', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $icp = CompanyIcp::factory()->create([
        'company_id' => $owner->company->id,
        'title' => 'EU founders',
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.store'), [
            'name' => 'LinkedIn visibility push',
            'type' => CampaignType::ThoughtLeadership->value,
            'objective' => CampaignObjective::Awareness->value,
            'budget_cents' => 500000,
            'company_icp_id' => $icp->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'LinkedIn visibility push')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.company_icp.title', 'EU founders')
        ->assertJsonPath('data.collab_counts.all', 0);

    $this->assertDatabaseHas('campaigns', [
        'company_id' => $owner->company->id,
        'name' => 'LinkedIn visibility push',
        'status' => CampaignStatus::Draft->value,
        'created_by_user_id' => $owner->id,
    ]);
});

test('creating a campaign requires a name', function () {
    $owner = User::factory()->company()->onboarded()->create();

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.store'), [
            'type' => CampaignType::Product->value,
            'objective' => CampaignObjective::Pipeline->value,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('data.name.0', 'The name field is required.');
});

test('creating a campaign rejects another workspace ICP', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $foreign = CompanyIcp::factory()->create();

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.store'), [
            'name' => 'Bad ICP',
            'type' => CampaignType::Product->value,
            'objective' => CampaignObjective::Pipeline->value,
            'company_icp_id' => $foreign->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('data.company_icp_id.0', 'The selected ICP is invalid.');
});

test('company users can view a campaign with brief counts shortlist posts and leads', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'brief' => ['context' => 'Build visibility.', 'key_message' => 'See the work.'],
    ]);
    $selected = marketplaceCreator(['display_name' => 'Shortlisted Ada']);
    $booked = marketplaceCreator(['display_name' => 'Booked Ada']);

    $selectedCollab = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $selected->id,
        'status' => CollaborationStatus::Selected,
    ]);
    $bookedCollab = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $booked->id,
        'status' => CollaborationStatus::Booked,
    ]);
    Post::factory()->create([
        'collaboration_id' => $bookedCollab->id,
        'status' => PostStatus::Published,
        'body' => 'Anyone can say they are a great developer.',
        'published_url' => 'https://linkedin.com/posts/1',
    ]);
    Lead::factory()->create([
        'company_id' => $owner->company->id,
        'campaign_id' => $campaign->id,
        'source' => LeadSource::Manual,
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.show', $campaign))
        ->assertOk()
        ->assertJsonPath('data.id', $campaign->id)
        ->assertJsonPath('data.brief.context', 'Build visibility.')
        ->assertJsonPath('data.collab_counts.all', 2)
        ->assertJsonPath('data.collab_counts.active', 1)
        ->assertJsonPath('data.collab_counts.todo', 1)
        ->assertJsonPath('data.shortlisted.0.id', $selected->id)
        ->assertJsonPath('data.shortlisted.0.collaboration_id', $selectedCollab->id)
        ->assertJsonPath('data.leads_count', 1)
        ->assertJsonPath('data.posts.0.creator.display_name', 'Booked Ada')
        ->assertJsonPath('data.posts.0.status', 'published');
});

test('company users cannot view another workspace campaign', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $other = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $other->company->id,
        'created_by_user_id' => $other->id,
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.show', $campaign))
        ->assertNotFound();
});

test('updating a brief flattens goal key messages and guidelines', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Draft,
    ]);

    $this->actingAs($owner)
        ->patchJson(route('api.company.campaigns.update', $campaign), [
            'brief' => [
                'context' => 'Show the work before the call.',
                'differentiators' => ['Real repos', 'Case studies'],
                'key_message' => 'See the work before you take the call.',
                'editorial' => [
                    'do' => ['Use concrete stack language'],
                    'avoid' => ['Invent testimonials'],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.brief.key_message', 'See the work before you take the call.')
        ->assertJsonPath('data.goal', 'Show the work before the call.')
        ->assertJsonPath('data.key_messages.0', 'Real repos')
        ->assertJsonPath('data.key_messages.2', 'See the work before you take the call.');

    $campaign->refresh();

    expect($campaign->guidelines)->toContain('Do: Use concrete stack language')
        ->and($campaign->guidelines)->toContain('Avoid: Invent testimonials');
});

test('campaign status transitions follow the allowed paths', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Draft,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.launch', $campaign))
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.pause', $campaign))
        ->assertOk()
        ->assertJsonPath('data.status', 'paused');

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.resume', $campaign))
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.complete', $campaign))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.reopen', $campaign))
        ->assertOk()
        ->assertJsonPath('data.status', 'active');
});

test('invalid campaign transitions are rejected', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Completed,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.launch', $campaign))
        ->assertUnprocessable();

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.cancel', $campaign))
        ->assertUnprocessable();
});

test('resume is rejected unless the campaign is paused', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Draft,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.resume', $campaign))
        ->assertUnprocessable();
});

test('completed and cancelled campaigns can be reopened to active', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $completed = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Completed,
    ]);
    $cancelled = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Cancelled,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.reopen', $completed))
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.reopen', $cancelled))
        ->assertOk()
        ->assertJsonPath('data.status', 'active');
});

test('cancelled campaigns are hidden from the default list', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $live = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Active,
        'name' => 'Live',
    ]);
    Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Cancelled,
        'name' => 'Dead',
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.id', $live->id);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.index', ['status' => 'cancelled']))
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.name', 'Dead');
});
