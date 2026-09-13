<?php

use App\Enums\LeadSource;
use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Lead;
use App\Models\PostMetric;
use App\Models\TrackingClick;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Str;

test('companies can read campaign analytics from post metrics and leads', function () {
    [$owner, $collaboration] = bookedDeal();
    $post = $collaboration->posts()->first();
    $campaign = $collaboration->campaign;

    PostMetric::query()->create([
        'post_id' => $post->id,
        'impressions' => 1000,
        'likes' => 12,
        'comments' => 3,
        'clicks' => 40,
        'unique_clicks' => 25,
        'qualified_clicks' => 8,
        'leads_count' => 2,
        'captured_at' => now(),
    ]);
    Lead::factory()->create([
        'company_id' => $owner->company->id,
        'campaign_id' => $campaign->id,
        'post_id' => $post->id,
        'source' => LeadSource::Manual,
        'payload' => ['pipeline_cents' => 150000],
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.analytics.show', $campaign))
        ->assertOk()
        ->assertJsonPath('data.impressions', 1000)
        ->assertJsonPath('data.clicks', 40)
        ->assertJsonPath('data.unique_clicks', 25)
        ->assertJsonPath('data.qualified_clicks', 8)
        ->assertJsonPath('data.leads_count', 1)
        ->assertJsonPath('data.pipeline_cents', 150000)
        ->assertJsonPath('data.ctr', 0.025);

    $this->actingAs($owner)
        ->getJson(route('api.company.posts.metrics.show', $post))
        ->assertOk()
        ->assertJsonPath('data.impressions', 1000)
        ->assertJsonPath('data.ctr', 0.025);
});

test('companies cannot read another workspace analytics', function () {
    [, $collaboration] = bookedDeal();
    $other = User::factory()->company()->onboarded()->create();
    $post = $collaboration->posts()->first();

    $this->actingAs($other)
        ->getJson(route('api.company.campaigns.analytics.show', $collaboration->campaign))
        ->assertNotFound();

    $this->actingAs($other)
        ->getJson(route('api.company.posts.metrics.show', $post))
        ->assertNotFound();

    $this->actingAs($other)
        ->getJson(route('api.company.analytics.overview'))
        ->assertOk()
        ->assertJsonPath('data.impressions', 0);
});

test('companies can record a manual lead and set pipeline value', function () {
    [$owner, $collaboration] = bookedDeal();
    $campaign = $collaboration->campaign;

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.leads.store', $campaign), [
            'source' => 'manual',
            'payload' => ['note' => 'Inbound from LinkedIn'],
        ])
        ->assertOk()
        ->assertJsonPath('data.source', 'manual');

    $lead = Lead::query()->where('campaign_id', $campaign->id)->latest('id')->first();

    $this->actingAs($owner)
        ->patchJson(route('api.company.leads.update', $lead), [
            'payload' => ['pipeline_cents' => 80000],
        ])
        ->assertOk()
        ->assertJsonPath('data.payload.pipeline_cents', 80000);
});

test('impressions ingest updates ctr on the post snapshot', function () {
    [$owner, $collaboration] = bookedDeal();
    $post = $collaboration->posts()->first();

    PostMetric::query()->create([
        'post_id' => $post->id,
        'impressions' => 0,
        'clicks' => 10,
        'unique_clicks' => 4,
        'likes' => 0,
        'comments' => 0,
        'qualified_clicks' => 0,
        'leads_count' => 0,
        'captured_at' => now(),
    ]);

    $this->artisan('metrics:ingest', [
        'post' => $post->id,
        '--impressions' => 200,
        '--likes' => 5,
        '--comments' => 1,
    ])->assertSuccessful();

    $this->actingAs($owner)
        ->getJson(route('api.company.posts.metrics.show', $post))
        ->assertOk()
        ->assertJsonPath('data.impressions', 200)
        ->assertJsonPath('data.likes', 5)
        ->assertJsonPath('data.comments', 1)
        ->assertJsonPath('data.ctr', 0.02);
});

test('workspace overview includes spend from posted captures', function () {
    [$owner, $collaboration] = bookedDeal();
    $wallet = $owner->company->wallet;

    WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'campaign_id' => $collaboration->campaign_id,
        'collaboration_id' => $collaboration->id,
        'type' => WalletTransactionType::Capture,
        'direction' => WalletTransactionDirection::Debit,
        'amount_cents' => 24000,
        'status' => WalletTransactionStatus::Posted,
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.analytics.overview'))
        ->assertOk()
        ->assertJsonPath('data.spend_cents', 24000);
});

test('workspace overview returns a daily series without loading click rows', function () {
    $this->travelTo('2026-09-13 12:00:00');

    [$owner, $collaboration] = bookedDeal();
    $post = $collaboration->posts()->first();
    $link = $collaboration->trackingLinks()->first();

    TrackingClick::factory()->create([
        'tracking_link_id' => $link->id,
        'post_id' => $post->id,
        'occurred_at' => now()->subDays(2),
    ]);
    TrackingClick::factory()->create([
        'tracking_link_id' => $link->id,
        'post_id' => $post->id,
        'occurred_at' => now()->subDays(2)->addHour(),
        'visitor_key' => (string) Str::uuid(),
    ]);
    Lead::factory()->create([
        'company_id' => $owner->company->id,
        'campaign_id' => $collaboration->campaign_id,
        'post_id' => $post->id,
        'occurred_at' => now()->subDay(),
        'payload' => ['pipeline_cents' => 50000],
    ]);

    $response = $this->actingAs($owner)
        ->getJson(route('api.company.analytics.overview', [
            'from' => '2026-09-11',
            'to' => '2026-09-13',
        ]))
        ->assertOk()
        ->assertJsonPath('data.from', '2026-09-11')
        ->assertJsonPath('data.to', '2026-09-13')
        ->assertJsonCount(3, 'data.series');

    expect($response->json('data.series.0'))->toMatchArray([
        'day' => '2026-09-11',
        'clicks' => 2,
        'unique_clicks' => 2,
        'leads' => 0,
        'spend_cents' => 0,
    ])->and($response->json('data.series.1.leads'))->toBe(1)
        ->and($response->json('data.leads_count'))->toBe(1)
        ->and($response->json('data.pipeline_cents'))->toBe(50000);
});

test('workspace overview rejects a range longer than 90 days', function () {
    $user = User::factory()->company()->onboarded()->create();

    $this->actingAs($user)
        ->getJson(route('api.company.analytics.overview', [
            'from' => '2026-01-01',
            'to' => '2026-04-02',
        ]))
        ->assertUnprocessable()
        ->assertJsonPath('data.to.0', 'The range may not exceed 90 days.');
});

test('companies can download a campaign report json payload', function () {
    [$owner, $collaboration] = bookedDeal();
    $campaign = $collaboration->campaign;
    $other = User::factory()->company()->onboarded()->create();

    $this->actingAs($owner)
        ->getJson(route('api.company.reports.campaigns.show', $campaign))
        ->assertOk()
        ->assertJsonPath('data.spend_cents', 0)
        ->assertJsonPath('data.creators.0.collaboration_id', $collaboration->id);

    $this->actingAs($other)
        ->getJson(route('api.company.reports.campaigns.show', $campaign))
        ->assertNotFound();
});
