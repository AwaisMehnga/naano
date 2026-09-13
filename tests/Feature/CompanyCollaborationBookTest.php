<?php

use App\Enums\CampaignStatus;
use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Enums\ContractStatus;
use App\Enums\PostStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\Post;
use App\Models\User;
use App\Models\Wallet;

test('owners can book a selected collaboration when the wallet is funded', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Selected,
        'source' => CollaborationSource::Invite,
    ]);
    Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 50000,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.book', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.status', 'booked')
        ->assertJsonPath('data.booked_price_cents', 24000)
        ->assertJsonPath('data.booked_posts_count', 1);

    expect($collaboration->fresh()->status)->toBe(CollaborationStatus::Booked)
        ->and($owner->company->wallet->fresh()->available_cents)->toBe(26000);

    $this->assertDatabaseHas('wallet_transactions', [
        'collaboration_id' => $collaboration->id,
        'type' => WalletTransactionType::Hold->value,
        'status' => WalletTransactionStatus::Posted->value,
        'amount_cents' => 24000,
    ]);

    $this->assertDatabaseHas('contracts', [
        'collaboration_id' => $collaboration->id,
        'status' => ContractStatus::Active->value,
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.collaborations.contract', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.terms.offer.price_cents', 24000)
        ->assertJsonPath('data.terms.version', 1);
});

test('booking an underfunded collaboration returns a checkout url', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'status' => CollaborationStatus::Selected,
    ]);
    Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 100,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.book', $collaboration))
        ->assertUnprocessable()
        ->assertJsonPath('data.checkout_url', fn ($url) => is_string($url) && $url !== '')
        ->assertJsonPath('data.required_cents', 24000)
        ->assertJsonPath('data.available_cents', 100);

    expect($collaboration->fresh()->status)->toBe(CollaborationStatus::Selected);
});

test('members cannot book a collaboration', function () {
    [$owner, $company, $member] = companyWithMember();
    $campaign = Campaign::factory()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => marketplaceCreator()->id,
        'status' => CollaborationStatus::Selected,
    ]);
    Wallet::factory()->create([
        'company_id' => $company->id,
        'available_cents' => 50000,
    ]);

    $this->actingAs($member)
        ->withHeaders(['X-Company-Id' => (string) $company->id])
        ->postJson(route('api.company.collaborations.book', $collaboration))
        ->assertForbidden();
});

test('invited collaborations cannot be booked', function () {
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
    Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 50000,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.book', $collaboration))
        ->assertUnprocessable();
});

test('cancelling a booked collaboration releases the hold and voids the contract', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Selected,
    ]);
    Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 50000,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.book', $collaboration))
        ->assertOk();

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.cancel', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($owner->company->wallet->fresh()->available_cents)->toBe(50000)
        ->and($collaboration->fresh()->contract->status)->toBe(ContractStatus::Void);

    $this->assertDatabaseHas('wallet_transactions', [
        'collaboration_id' => $collaboration->id,
        'type' => WalletTransactionType::Release->value,
        'status' => WalletTransactionStatus::Posted->value,
        'amount_cents' => 24000,
    ]);
});

test('cancelling after a published post returns 422 and leaves the hold', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Selected,
    ]);
    Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 50000,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.book', $collaboration))
        ->assertOk();

    Post::factory()->create([
        'collaboration_id' => $collaboration->id,
        'status' => PostStatus::Published,
        'published_url' => 'https://www.linkedin.com/posts/ada-live',
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.cancel', $collaboration))
        ->assertUnprocessable()
        ->assertJsonPath('data.status.0', 'This collaboration cannot be cancelled after a post is published.');

    expect($collaboration->fresh()->status)->toBe(CollaborationStatus::Booked)
        ->and($owner->company->wallet->fresh()->available_cents)->toBe(26000);

    $this->assertDatabaseHas('contracts', [
        'collaboration_id' => $collaboration->id,
        'status' => ContractStatus::Active->value,
    ]);
});

test('collaboration lists mark a published post so cancel can be hidden', function () {
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
    Post::factory()->create([
        'collaboration_id' => $collaboration->id,
        'status' => PostStatus::Published,
        'published_url' => 'https://www.linkedin.com/posts/ada-live',
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.collaborations.index', $campaign))
        ->assertOk()
        ->assertJsonPath('data.0.id', $collaboration->id)
        ->assertJsonPath('data.0.has_published_post', true);
});

test('company select does not set accepted_at', function () {
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

    expect($collaboration->fresh()->accepted_at)->toBeNull();
});

test('owners can book a listed creator onto a campaign', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();
    Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 50000,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.book', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'booked')
        ->assertJsonPath('data.booked_price_cents', 24000)
        ->assertJsonPath('data.creator.id', $creator->id);

    $collaboration = Collaboration::query()
        ->where('campaign_id', $campaign->id)
        ->where('creator_profile_id', $creator->id)
        ->first();

    expect($collaboration)->not->toBeNull()
        ->and($collaboration->status)->toBe(CollaborationStatus::Booked)
        ->and($collaboration->source)->toBe(CollaborationSource::Invite)
        ->and($owner->company->wallet->fresh()->available_cents)->toBe(26000);

    $this->assertDatabaseHas('wallet_transactions', [
        'collaboration_id' => $collaboration->id,
        'type' => WalletTransactionType::Hold->value,
        'status' => WalletTransactionStatus::Posted->value,
        'amount_cents' => 24000,
    ]);
});

test('booking a listed creator reuses a selected collaboration', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Selected,
        'source' => CollaborationSource::Invite,
    ]);
    Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 50000,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.book', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.id', $collaboration->id)
        ->assertJsonPath('data.status', 'booked');

    expect(Collaboration::query()->where('campaign_id', $campaign->id)->count())->toBe(1);
});

test('booking a listed creator when underfunded returns a checkout url', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();
    Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 100,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.book', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('data.checkout_url', fn ($url) => is_string($url) && $url !== '')
        ->assertJsonPath('data.required_cents', 24000)
        ->assertJsonPath('data.available_cents', 100);

    $collaboration = Collaboration::query()
        ->where('campaign_id', $campaign->id)
        ->where('creator_profile_id', $creator->id)
        ->first();

    expect($collaboration)->not->toBeNull()
        ->and($collaboration->status)->toBe(CollaborationStatus::Selected);
});

test('members cannot book a listed creator', function () {
    [$owner, $company, $member] = companyWithMember();
    $campaign = Campaign::factory()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $owner->id,
    ]);
    Wallet::factory()->create([
        'company_id' => $company->id,
        'available_cents' => 50000,
    ]);

    $this->actingAs($member)
        ->withHeaders(['X-Company-Id' => (string) $company->id])
        ->postJson(route('api.company.campaigns.book', $campaign), [
            'creator_profile_id' => marketplaceCreator()->id,
        ])
        ->assertForbidden();
});

test('booking a listed creator who is already booked returns 422', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();
    Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Booked,
    ]);
    Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 50000,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.book', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('data.status.0', 'This creator is already booked on the campaign.');
});

test('booking a listed creator onto a closed campaign returns 422', function (CampaignStatus $status) {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => $status,
    ]);
    Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 50000,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.book', $campaign), [
            'creator_profile_id' => marketplaceCreator()->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('data.campaign.0', 'This campaign cannot accept bookings.');
})->with([
    CampaignStatus::Completed,
    CampaignStatus::Cancelled,
]);

test('booking a listed creator who left the pipeline returns 422', function (CollaborationStatus $status) {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();
    Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'status' => $status,
    ]);
    Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 50000,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.book', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('data.creator_profile_id.0', 'This creator is already on the campaign.');
})->with([
    CollaborationStatus::Declined,
    CollaborationStatus::Cancelled,
]);

test('booking a listed creator requires a creator profile id', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.book', $campaign))
        ->assertUnprocessable()
        ->assertJsonPath('data.creator_profile_id.0', 'The creator profile id field is required.');
});

test('company users cannot book a listed creator onto another workspace campaign', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $other = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $other->company->id,
        'created_by_user_id' => $other->id,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.book', $campaign), [
            'creator_profile_id' => marketplaceCreator()->id,
        ])
        ->assertNotFound();
});

test('owners can source a creator and list company collaborations', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator(['display_name' => 'Sourced Ada']);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.sourcing.store', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.source', 'sourced')
        ->assertJsonPath('data.status', 'outreach');

    $this->actingAs($owner)
        ->getJson(route('api.company.collaborations.index'))
        ->assertOk()
        ->assertJsonPath('data.data.0.creator.display_name', 'Sourced Ada')
        ->assertJsonPath('data.counts.todo', 1)
        ->assertJsonPath('data.per_page', 25);
});
