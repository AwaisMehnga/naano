<?php

use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Enums\ContractStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Campaign;
use App\Models\Collaboration;
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
        ->assertJsonPath('data.0.creator.display_name', 'Sourced Ada');
});
