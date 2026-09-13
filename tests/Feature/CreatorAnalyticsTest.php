<?php

use App\Models\PostMetric;

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
        ->assertJsonPath('data.earnings_cents', 0);

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
