<?php

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;

test('owners can view an empty wallet', function () {
    $owner = User::factory()->company()->onboarded()->create();

    $this->actingAs($owner)
        ->getJson(route('api.company.wallet.show'))
        ->assertOk()
        ->assertJsonPath('data.available_cents', 0)
        ->assertJsonPath('data.held_cents', 0)
        ->assertJsonPath('data.currency', 'EUR');
});

test('owners can start a wallet top-up', function () {
    $owner = User::factory()->company()->onboarded()->create();

    $this->actingAs($owner)
        ->postJson(route('api.company.wallet.topups.store'), [
            'amount_cents' => 10000,
        ])
        ->assertOk()
        ->assertJsonPath('data.stripe_session_id', fn ($id) => is_string($id) && str_starts_with($id, 'cs_test_'))
        ->assertJsonPath('data.checkout_url', fn ($url) => is_string($url) && str_contains($url, 'checkout.stripe.test'));

    $this->assertDatabaseHas('wallet_transactions', [
        'type' => WalletTransactionType::Topup->value,
        'status' => WalletTransactionStatus::Pending->value,
        'amount_cents' => 10000,
    ]);
});

test('top-ups below the minimum are rejected', function () {
    $owner = User::factory()->company()->onboarded()->create();

    $this->actingAs($owner)
        ->postJson(route('api.company.wallet.topups.store'), [
            'amount_cents' => 100,
        ])
        ->assertUnprocessable();
});

test('owners can poll a pending top-up', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $wallet = Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 0,
    ]);
    $transaction = WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'type' => WalletTransactionType::Topup,
        'status' => WalletTransactionStatus::Pending,
        'amount_cents' => 8000,
        'stripe_id' => 'cs_test_poll',
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.wallet.topups.show', $transaction))
        ->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.amount_cents', 8000);
});

test('a company cannot poll another workspace top-up', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $other = User::factory()->company()->onboarded()->create();
    $wallet = Wallet::factory()->create([
        'company_id' => $other->company->id,
    ]);
    $transaction = WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'type' => WalletTransactionType::Topup,
        'status' => WalletTransactionStatus::Pending,
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.wallet.topups.show', $transaction))
        ->assertNotFound();
});
