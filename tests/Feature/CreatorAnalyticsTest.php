<?php

use App\Enums\PostStatus;
use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\CreatorAudienceProfile;
use App\Models\PostMetric;
use App\Models\TrackingClick;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;

test('creators can read deal and post metrics for their own posts', function () {
    [, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();

    PostMetric::query()->create([
        'post_id' => $post->id,
        'impressions' => 500,
        'likes' => 9,
        'comments' => 2,
        'clicks' => 20,
        'unique_clicks' => 10,
        'qualified_clicks' => 3,
        'leads_count' => 1,
        'captured_at' => now(),
    ]);

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.analytics.overview'))
        ->assertOk()
        ->assertJsonPath('data.impressions', 500)
        ->assertJsonPath('data.clicks', 20)
        ->assertJsonPath('data.unique_clicks', 10)
        ->assertJsonPath('data.engagement', 11)
        ->assertJsonPath('data.public_posts_count', 0)
        ->assertJsonPath('data.followers_count', null)
        ->assertJsonPath('data.earnings_cents', 0)
        ->assertJsonStructure([
            'data' => [
                'from',
                'to',
                'series',
                'comparison',
                'audience_segments',
                'growth' => ['clicks', 'earnings'],
                'period' => ['clicks', 'unique_clicks', 'earnings_cents'],
                'active_deals_count',
                'completed_deals_count',
                'connections_count',
            ],
        ]);

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.collaborations.index'))
        ->assertOk()
        ->assertJsonPath('data.0.metrics.impressions', 500)
        ->assertJsonPath('data.0.metrics.unique_clicks', 10);

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.collaborations.metrics.show', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.impressions', 500)
        ->assertJsonPath('data.ctr', 0.02);

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.posts.metrics.show', $post))
        ->assertOk()
        ->assertJsonPath('data.unique_clicks', 10);
});

test('creator overview counts published posts and followers', function () {
    [, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'status' => PostStatus::Published,
        'published_url' => 'https://www.linkedin.com/posts/ada-live',
    ]);
    CreatorAudienceProfile::factory()->create([
        'creator_profile_id' => $collaboration->creator_profile_id,
        'followers_count' => 4200,
        'connections_count' => 1800,
        'audience_mix' => [
            'seniority' => [
                'Director' => 42,
                'Manager' => 28,
                'IC' => 30,
            ],
        ],
        'captured_at' => now(),
    ]);

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.analytics.overview'))
        ->assertOk()
        ->assertJsonPath('data.public_posts_count', 1)
        ->assertJsonPath('data.followers_count', 4200)
        ->assertJsonPath('data.connections_count', 1800)
        ->assertJsonPath('data.audience_segments.0.label', 'Director')
        ->assertJsonPath('data.audience_segments.0.value', 42)
        ->assertJsonPath('data.active_deals_count', 1);
});

test('creator overview series respects date range filter', function () {
    $this->travelTo('2026-09-22 12:00:00');

    [, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $link = $collaboration->trackingLinks()->first();
    $wallet = $collaboration->campaign->company->wallet;

    TrackingClick::factory()->create([
        'tracking_link_id' => $link->id,
        'post_id' => $post->id,
        'occurred_at' => Carbon::parse('2026-09-20 10:00:00'),
    ]);
    TrackingClick::factory()->create([
        'tracking_link_id' => $link->id,
        'post_id' => $post->id,
        'occurred_at' => Carbon::parse('2026-09-10 10:00:00'),
    ]);

    $transaction = WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'campaign_id' => $collaboration->campaign_id,
        'collaboration_id' => $collaboration->id,
        'type' => WalletTransactionType::Capture,
        'direction' => WalletTransactionDirection::Debit,
        'amount_cents' => 25000,
        'status' => WalletTransactionStatus::Posted,
    ]);
    $transaction->forceFill([
        'created_at' => Carbon::parse('2026-09-20 12:00:00'),
        'updated_at' => Carbon::parse('2026-09-20 12:00:00'),
    ])->save();

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.analytics.overview', [
            'from' => '2026-09-18',
            'to' => '2026-09-22',
        ]))
        ->assertOk()
        ->assertJsonPath('data.from', '2026-09-18')
        ->assertJsonPath('data.to', '2026-09-22')
        ->assertJsonPath('data.period.clicks', 1)
        ->assertJsonPath('data.period.earnings_cents', 25000);

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.analytics.overview', [
            'from' => '2026-09-01',
            'to' => '2026-12-01',
        ]))
        ->assertUnprocessable();
});

test('creators cannot read another creator metrics', function () {
    [, $collaboration] = bookedDeal();
    $other = marketplaceCreator(['display_name' => 'Other Ada']);
    $post = $collaboration->posts()->first();

    $this->actingAs($other->user)
        ->getJson(route('api.creator.collaborations.metrics.show', $collaboration))
        ->assertNotFound();

    $this->actingAs($other->user)
        ->getJson(route('api.creator.posts.metrics.show', $post))
        ->assertNotFound();
});
