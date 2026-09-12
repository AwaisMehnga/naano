<?php

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;

test('an unsigned webhook is rejected', function () {
    $this->postJson(route('api.stripe.webhook'), [
        'id' => 'evt_1',
        'type' => 'checkout.session.completed',
    ])
        ->assertStatus(400);
});

test('checkout session completed posts a pending top-up', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $wallet = Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 0,
    ]);
    $transaction = WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'type' => WalletTransactionType::Topup,
        'status' => WalletTransactionStatus::Pending,
        'amount_cents' => 12000,
    ]);

    $payload = [
        'id' => 'evt_checkout_1',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_1',
                'customer' => 'cus_live_1',
                'metadata' => [
                    'company_id' => (string) $owner->company->id,
                    'wallet_transaction_id' => (string) $transaction->id,
                ],
            ],
        ],
    ];

    $this->postJson(route('api.stripe.webhook'), $payload, [
        'Stripe-Signature' => 'test',
    ])->assertOk();

    expect($transaction->fresh()->status)->toBe(WalletTransactionStatus::Posted)
        ->and($wallet->fresh()->available_cents)->toBe(12000)
        ->and($owner->company->fresh()->stripe_customer_id)->toBe('cus_live_1');
});

test('duplicate webhook events do not credit twice', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $wallet = Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 0,
    ]);
    $transaction = WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'type' => WalletTransactionType::Topup,
        'status' => WalletTransactionStatus::Pending,
        'amount_cents' => 5000,
    ]);

    $payload = [
        'id' => 'evt_dup_1',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'metadata' => [
                    'wallet_transaction_id' => (string) $transaction->id,
                ],
            ],
        ],
    ];

    $this->postJson(route('api.stripe.webhook'), $payload, [
        'Stripe-Signature' => 'test',
    ])->assertOk();

    $this->postJson(route('api.stripe.webhook'), $payload, [
        'Stripe-Signature' => 'test',
    ])->assertOk();

    expect($wallet->fresh()->available_cents)->toBe(5000);
});

test('payment intent failed marks a pending top-up as failed', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $wallet = Wallet::factory()->create([
        'company_id' => $owner->company->id,
        'available_cents' => 0,
    ]);
    $transaction = WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'type' => WalletTransactionType::Topup,
        'status' => WalletTransactionStatus::Pending,
        'amount_cents' => 7000,
    ]);

    $this->postJson(route('api.stripe.webhook'), [
        'id' => 'evt_fail_1',
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'metadata' => [
                    'wallet_transaction_id' => (string) $transaction->id,
                ],
            ],
        ],
    ], [
        'Stripe-Signature' => 'test',
    ])->assertOk();

    expect($transaction->fresh()->status)->toBe(WalletTransactionStatus::Failed)
        ->and($wallet->fresh()->available_cents)->toBe(0);
});
