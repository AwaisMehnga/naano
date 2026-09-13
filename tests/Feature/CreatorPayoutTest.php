<?php

use App\Enums\CollaborationStatus;
use App\Enums\PayoutStatus;
use App\Enums\PostStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Payout;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\CompanyWalletService;

test('publishing the last post captures the hold once and completes the collab', function () {
    [$owner, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Live proof belongs on LinkedIn.',
        'status' => PostStatus::Approved,
    ]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.publish', $post), [
            'published_url' => 'https://www.linkedin.com/posts/ada-live',
        ])
        ->assertOk();

    expect($owner->company->wallet->fresh()->available_cents)->toBe(26000)
        ->and($collaboration->fresh()->status)->toBe(CollaborationStatus::Completed)
        ->and(WalletTransaction::query()
            ->where('collaboration_id', $collaboration->id)
            ->where('type', WalletTransactionType::Capture)
            ->where('status', WalletTransactionStatus::Posted)
            ->count())->toBe(1);

    app(CompanyWalletService::class)->captureHold($owner->company, $collaboration->fresh());

    expect(WalletTransaction::query()
        ->where('collaboration_id', $collaboration->id)
        ->where('type', WalletTransactionType::Capture)
        ->count())->toBe(1);
});

test('the first of two booked posts does not capture until both are published', function () {
    [$owner, $collaboration, $creatorUser] = bookedDeal();
    $collaboration->update(['booked_posts_count' => 2]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.collaborations.posts.store', $collaboration), [
            'body' => 'Second post.',
        ])
        ->assertOk();

    $collaboration->posts()->update([
        'status' => PostStatus::Approved,
        'body' => 'Ready to go live.',
    ]);

    $posts = $collaboration->posts()->orderBy('id')->get();

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.publish', $posts[0]), [
            'published_url' => 'https://www.linkedin.com/posts/ada-1',
        ])
        ->assertOk();

    expect($collaboration->fresh()->status)->toBe(CollaborationStatus::Booked)
        ->and(WalletTransaction::query()
            ->where('collaboration_id', $collaboration->id)
            ->where('type', WalletTransactionType::Capture)
            ->count())->toBe(0);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.publish', $posts[1]), [
            'published_url' => 'https://www.linkedin.com/posts/ada-2',
        ])
        ->assertOk();

    expect($collaboration->fresh()->status)->toBe(CollaborationStatus::Completed)
        ->and(WalletTransaction::query()
            ->where('collaboration_id', $collaboration->id)
            ->where('type', WalletTransactionType::Capture)
            ->where('status', WalletTransactionStatus::Posted)
            ->count())->toBe(1)
        ->and($owner->company->wallet->fresh()->available_cents)->toBe(26000);
});

test('creator wallet pending becomes available after the live URL capture', function () {
    [, $collaboration, $creatorUser] = bookedDeal();

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.wallet.show'))
        ->assertOk()
        ->assertJsonPath('data.pending_cents', 24000)
        ->assertJsonPath('data.available_cents', 0)
        ->assertJsonPath('data.currency', 'EUR')
        ->assertJsonPath('data.withdrawable', false);

    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Live proof belongs on LinkedIn.',
        'status' => PostStatus::Approved,
    ]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.publish', $post), [
            'published_url' => 'https://www.linkedin.com/posts/ada-live',
        ])
        ->assertOk();

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.wallet.show'))
        ->assertOk()
        ->assertJsonPath('data.pending_cents', 0)
        ->assertJsonPath('data.available_cents', 24000);
});

test('withdrawals return 422 without Connect payouts or below the minimum', function () {
    [, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Live proof belongs on LinkedIn.',
        'status' => PostStatus::Approved,
    ]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.publish', $post), [
            'published_url' => 'https://www.linkedin.com/posts/ada-live',
        ])
        ->assertOk();

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.wallet.withdrawals.store'), [
            'amount_cents' => 24000,
        ])
        ->assertUnprocessable();

    $creatorUser->creatorProfile->update([
        'payouts_enabled' => true,
        'stripe_connect_id' => 'acct_fake_1',
    ]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.wallet.withdrawals.store'), [
            'amount_cents' => 100,
        ])
        ->assertUnprocessable();
});

test('a withdrawable creator can request a transfer and the webhook marks it in transit', function () {
    [, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Live proof belongs on LinkedIn.',
        'status' => PostStatus::Approved,
    ]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.publish', $post), [
            'published_url' => 'https://www.linkedin.com/posts/ada-live',
        ])
        ->assertOk();

    $creatorUser->creatorProfile->update([
        'payouts_enabled' => true,
        'stripe_connect_id' => 'acct_fake_1',
    ]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.wallet.withdrawals.store'), [
            'amount_cents' => 24000,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.stripe_transfer_id', fn ($id) => is_string($id) && str_starts_with($id, 'tr_fake_'));

    $payout = Payout::query()->where('creator_profile_id', $creatorUser->creatorProfile->id)->first();

    $this->postJson(route('api.stripe.webhook'), [
        'id' => 'evt_transfer_1',
        'type' => 'transfer.created',
        'data' => [
            'object' => [
                'id' => $payout->stripe_transfer_id,
                'metadata' => [
                    'payout_id' => (string) $payout->id,
                ],
            ],
        ],
    ], [
        'Stripe-Signature' => 'test',
    ])->assertOk();

    expect($payout->fresh()->status)->toBe(PayoutStatus::InTransit);
});

test('v2 account updated enables Connect payouts', function () {
    $user = User::factory()->creator()->onboarded()->create();
    $user->creatorProfile->update(['stripe_connect_id' => 'acct_evt_v2']);

    $this->postJson(route('api.stripe.webhook'), [
        'id' => 'evt_account_v2',
        'type' => 'v2.core.account.updated',
        'data' => [
            'object' => [
                'id' => 'acct_evt_v2',
                'configuration' => [
                    'recipient' => [
                        'capabilities' => [
                            'stripe_balance' => [
                                'payouts' => [
                                    'status' => 'active',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ], [
        'Stripe-Signature' => 'test',
    ])->assertOk();

    expect($user->creatorProfile->fresh()->payouts_enabled)->toBeTrue();
});

test('account updated enables Connect payouts', function () {
    $user = User::factory()->creator()->onboarded()->create();
    $user->creatorProfile->update(['stripe_connect_id' => 'acct_evt_1']);

    $this->postJson(route('api.stripe.webhook'), [
        'id' => 'evt_account_1',
        'type' => 'account.updated',
        'data' => [
            'object' => [
                'id' => 'acct_evt_1',
                'payouts_enabled' => true,
            ],
        ],
    ], [
        'Stripe-Signature' => 'test',
    ])->assertOk();

    expect($user->creatorProfile->fresh()->payouts_enabled)->toBeTrue();
});

test('a creator cannot view another creators payout', function () {
    $user = User::factory()->creator()->onboarded()->create();
    $other = marketplaceCreator();
    $payout = Payout::factory()->create([
        'creator_profile_id' => $other->id,
        'amount_cents' => 24000,
    ]);

    $this->actingAs($user)
        ->getJson(route('api.creator.payouts.show', $payout))
        ->assertNotFound();
});
