<?php

use App\Ai\Agents\CampaignFitAgent;
use App\Enums\CampaignStatus;
use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\CompanyIcp;
use App\Models\CreatorMatchScore;
use App\Models\Niche;
use App\Models\User;

test('vetted creators can list and apply to a related active campaign', function () {
    fakeCampaignFit();

    $owner = User::factory()->company()->onboarded()->create();
    $owner->company->update(['country' => 'FR']);
    $deadline = now()->addDays(12);
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Active,
        'name' => 'Open brief',
        'brief' => ['context' => 'Ship a LinkedIn post.'],
        'end_at' => $deadline,
    ]);
    $creator = marketplaceCreator();
    creatorMatchScore($campaign, $creator);

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $campaign->id)
        ->assertJsonPath('data.0.name', 'Open brief')
        ->assertJsonPath('data.0.match_score', 82)
        ->assertJsonPath('data.0.audience_relevance', 74)
        ->assertJsonPath('data.0.location.country', 'FR')
        ->assertJsonPath('data.0.deadline', $deadline->toIso8601String())
        ->assertJsonPath('data.0.company.website', $owner->company->website)
        ->assertJsonPath('data.0.company.logo_url', null)
        ->assertJsonMissingPath('data.0.reasons');

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.show', $campaign))
        ->assertOk()
        ->assertJsonPath('data.brief.context', 'Ship a LinkedIn post.')
        ->assertJsonPath('data.match_score', 82)
        ->assertJsonMissingPath('data.reasons');

    $this->actingAs($creator->user)
        ->postJson(route('api.creator.opportunities.apply', $campaign))
        ->assertOk()
        ->assertJsonPath('data.status', 'applied')
        ->assertJsonPath('data.source', 'apply');

    $this->actingAs($owner)
        ->getJson(route('api.company.collaborations.index', [
            'pipeline' => 'invitations_received',
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.source', 'apply')
        ->assertJsonPath('data.data.0.status', 'applied');
});

test('a creator cannot apply twice to the same campaign', function () {
    fakeCampaignFit();

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
    CampaignFitAgent::fake()->preventStrayPrompts();

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

test('campaigns with a mismatched icp are hidden', function () {
    CampaignFitAgent::fake()->preventStrayPrompts();

    $owner = User::factory()->company()->onboarded()->create();
    $icp = CompanyIcp::factory()->create([
        'company_id' => $owner->company->id,
        'industries' => ['Healthcare'],
    ]);
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'company_icp_id' => $icp->id,
        'status' => CampaignStatus::Active,
    ]);
    $creator = marketplaceCreator();
    $creator->niches()->attach(
        Niche::factory()->create(['name' => 'SaaS', 'slug' => 'saas'])->id,
    );

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.index'))
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.show', $campaign))
        ->assertNotFound();
});

test('a region mismatch still lists the campaign', function () {
    CampaignFitAgent::fake()->preventStrayPrompts();

    $owner = User::factory()->company()->onboarded()->create();
    $icp = CompanyIcp::factory()->create([
        'company_id' => $owner->company->id,
        'regions' => ['US'],
    ]);
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'company_icp_id' => $icp->id,
        'status' => CampaignStatus::Active,
        'name' => 'US brief',
    ]);
    $creator = marketplaceCreator(['country' => 'FR']);
    creatorMatchScore($campaign, $creator, 40, 35);

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $campaign->id)
        ->assertJsonPath('data.0.match_score', 40);
});

test('low fit scores are omitted from opportunities', function () {
    CampaignFitAgent::fake()->preventStrayPrompts();

    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Active,
    ]);
    $creator = marketplaceCreator();
    creatorMatchScore($campaign, $creator, 29, 18);

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.index'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('creators can search related opportunities by name', function () {
    CampaignFitAgent::fake()->preventStrayPrompts();

    $owner = User::factory()->company()->onboarded()->create();
    $owner->company->update(['name' => 'Northwind']);
    Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Active,
        'name' => 'Keep hidden',
    ]);
    $visible = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Active,
        'name' => 'Launch week',
    ]);
    $creator = marketplaceCreator();
    creatorMatchScore($visible, $creator);
    creatorMatchScore(
        Campaign::query()->where('name', 'Keep hidden')->firstOrFail(),
        $creator,
    );

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.index', ['q' => 'launch']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $visible->id);
});

test('creators can filter opportunities by objective country match and sort', function () {
    CampaignFitAgent::fake()->preventStrayPrompts();

    $owner = User::factory()->company()->onboarded()->create();
    $owner->company->update(['country' => 'FR', 'name' => 'Paris Co']);
    $usOwner = User::factory()->company()->onboarded()->create();
    $usOwner->company->update(['country' => 'US', 'name' => 'US Co']);

    $fr = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Active,
        'objective' => 'awareness',
        'name' => 'Alpha FR',
        'end_at' => now()->addDays(30),
    ]);
    $us = Campaign::factory()->create([
        'company_id' => $usOwner->company->id,
        'created_by_user_id' => $usOwner->id,
        'status' => CampaignStatus::Active,
        'objective' => 'pipeline',
        'name' => 'Beta US',
        'end_at' => now()->addDays(5),
    ]);
    $creator = marketplaceCreator();
    creatorMatchScore($fr, $creator, 90);
    creatorMatchScore($us, $creator, 55);

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.index', [
            'objective' => 'awareness',
            'country' => 'FR',
            'match' => 70,
            'sort' => 'name',
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $fr->id);
});

test('country filter also matches icp regions', function () {
    CampaignFitAgent::fake()->preventStrayPrompts();

    $owner = User::factory()->company()->onboarded()->create();
    $owner->company->update(['country' => 'US']);
    $icp = CompanyIcp::factory()->create([
        'company_id' => $owner->company->id,
        'regions' => ['FR', 'DE'],
    ]);
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'company_icp_id' => $icp->id,
        'status' => CampaignStatus::Active,
        'name' => 'EU reach',
    ]);
    $creator = marketplaceCreator();
    creatorMatchScore($campaign, $creator);

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.index', ['country' => 'fr']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $campaign->id);
});

test('matching niche icps are scored and returned', function () {
    CampaignFitAgent::fake()->preventStrayPrompts();

    $owner = User::factory()->company()->onboarded()->create();
    $icp = CompanyIcp::factory()->create([
        'company_id' => $owner->company->id,
        'industries' => ['SaaS'],
        'regions' => ['FR'],
    ]);
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'company_icp_id' => $icp->id,
        'status' => CampaignStatus::Active,
        'name' => 'SaaS push',
    ]);
    $creator = marketplaceCreator();
    $creator->niches()->attach(
        Niche::factory()->create(['name' => 'SaaS', 'slug' => 'saas'])->id,
    );
    creatorMatchScore($campaign, $creator, 91, 88);

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $campaign->id)
        ->assertJsonPath('data.0.match_score', 91)
        ->assertJsonPath('data.0.location.regions.0', 'FR');

    expect(CreatorMatchScore::query()->where('campaign_id', $campaign->id)->exists())->toBeTrue();
});

test('listing opportunities does not call the fit agent and honours limit', function () {
    CampaignFitAgent::fake()->preventStrayPrompts();

    $owner = User::factory()->company()->onboarded()->create();
    $creator = marketplaceCreator();

    foreach (range(1, 5) as $index) {
        $campaign = Campaign::factory()->create([
            'company_id' => $owner->company->id,
            'created_by_user_id' => $owner->id,
            'status' => CampaignStatus::Active,
            'name' => 'Brief '.$index,
        ]);
        creatorMatchScore($campaign, $creator, 40 + $index, 40);
    }

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.opportunities.index', ['limit' => 3]))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.match_score', 45);
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

test('creators see the full campaign brief on a deal', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'brief' => [
            'context' => 'Show the work before the call.',
            'product' => 'Naano hiring workspace',
            'differentiators' => ['Real repos', 'Case studies'],
            'target' => 'Engineering managers in EU',
            'pains' => ['Hard to verify skill from a CV'],
            'trigger' => 'A team is hiring for a senior engineer',
            'key_message' => 'See the work before you take the call.',
            'audience' => [
                'industries' => 'Developer Tools',
                'geographies' => 'EU',
                'tone' => 'Direct and concrete',
            ],
            'editorial' => [
                'do' => ['Use concrete stack language'],
                'avoid' => ['Invent testimonials'],
            ],
            'references' => [
                [
                    'quote' => 'Most portfolios show projects, not thinking.',
                    'structure' => 'observation → proof → invite',
                ],
            ],
            'angles' => [
                [
                    'title' => 'Technical proof over claims',
                    'hook' => 'Anyone can say they are great. Not everyone can show receipts.',
                    'format' => 'Thought leadership',
                    'example' => 'Example post body.',
                ],
            ],
        ],
    ]);
    $creator = marketplaceCreator();
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Booked,
    ]);

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.collaborations.show', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.brief.key_message', 'See the work before you take the call.')
        ->assertJsonPath('data.brief.product', 'Naano hiring workspace')
        ->assertJsonPath('data.brief.audience.tone', 'Direct and concrete')
        ->assertJsonPath('data.brief.editorial.do.0', 'Use concrete stack language')
        ->assertJsonPath('data.brief.angles.0.hook', 'Anyone can say they are great. Not everyone can show receipts.');
});

test('creators can search deals by campaign name', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $creator = marketplaceCreator();
    $hidden = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'name' => 'Keep hidden',
    ]);
    $visible = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'name' => 'Launch week',
    ]);
    Collaboration::factory()->create([
        'campaign_id' => $hidden->id,
        'creator_profile_id' => $creator->id,
    ]);
    $deal = Collaboration::factory()->create([
        'campaign_id' => $visible->id,
        'creator_profile_id' => $creator->id,
    ]);

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.collaborations.index', ['q' => 'Launch']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $deal->id);
});

test('creators can search deals by company name', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $owner->company->update(['name' => 'Northwind']);
    $other = User::factory()->company()->onboarded()->create();
    $other->company->update(['name' => 'Acme']);
    $creator = marketplaceCreator();
    Collaboration::factory()->create([
        'campaign_id' => Campaign::factory()->create([
            'company_id' => $other->company->id,
            'created_by_user_id' => $other->id,
        ])->id,
        'creator_profile_id' => $creator->id,
    ]);
    $deal = Collaboration::factory()->create([
        'campaign_id' => Campaign::factory()->create([
            'company_id' => $owner->company->id,
            'created_by_user_id' => $owner->id,
        ])->id,
        'creator_profile_id' => $creator->id,
    ]);

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.collaborations.index', ['q' => 'Northwind']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $deal->id);
});

test('creators can filter deals by status', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $creator = marketplaceCreator();
    Collaboration::factory()->create([
        'campaign_id' => Campaign::factory()->create([
            'company_id' => $owner->company->id,
            'created_by_user_id' => $owner->id,
        ])->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Invited,
    ]);
    $deal = Collaboration::factory()->create([
        'campaign_id' => Campaign::factory()->create([
            'company_id' => $owner->company->id,
            'created_by_user_id' => $owner->id,
        ])->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Booked,
    ]);

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.collaborations.index', ['status' => 'booked']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $deal->id);
});

test('creators can filter deals by source', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $creator = marketplaceCreator();
    Collaboration::factory()->create([
        'campaign_id' => Campaign::factory()->create([
            'company_id' => $owner->company->id,
            'created_by_user_id' => $owner->id,
        ])->id,
        'creator_profile_id' => $creator->id,
        'source' => CollaborationSource::Invite,
    ]);
    $deal = Collaboration::factory()->create([
        'campaign_id' => Campaign::factory()->create([
            'company_id' => $owner->company->id,
            'created_by_user_id' => $owner->id,
        ])->id,
        'creator_profile_id' => $creator->id,
        'source' => CollaborationSource::Apply,
    ]);

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.collaborations.index', ['source' => 'apply']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $deal->id);
});

test('listing deals returns 422 for an invalid filter', function (string $field, string $value, string $message) {
    $creator = marketplaceCreator();

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.collaborations.index', [$field => $value]))
        ->assertUnprocessable()
        ->assertJsonPath('data.'.$field.'.0', $message);
})->with([
    'status' => ['status', 'nope', 'The selected status is invalid.'],
    'source' => ['source', 'nope', 'The selected source is invalid.'],
]);

test('listing deals returns 422 when the search query is too long', function () {
    $creator = marketplaceCreator();

    $this->actingAs($creator->user)
        ->getJson(route('api.creator.collaborations.index', ['q' => str_repeat('a', 121)]))
        ->assertUnprocessable()
        ->assertJsonPath('data.q.0', 'The q field must not be greater than 120 characters.');
});
