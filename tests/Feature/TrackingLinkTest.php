<?php

use App\Enums\CollaborationStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\User;
use App\Models\Wallet;

test('booking creates a unique hire tracking link', function () {
    [$owner, $collaboration] = bookedDeal();

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.tracking-links.index', $collaboration->campaign))
        ->assertOk()
        ->assertJsonPath('data.0.utm_source', 'naano')
        ->assertJsonPath('data.0.utm_medium', 'linkedin')
        ->assertJsonPath('data.0.utm_campaign', (string) $collaboration->campaign_id)
        ->assertJsonPath('data.0.utm_content', (string) $collaboration->id)
        ->assertJsonPath('data.0.short_url', fn ($url) => is_string($url) && str_contains($url, '/t/'));

    $this->assertDatabaseHas('tracking_links', [
        'collaboration_id' => $collaboration->id,
        'post_id' => $collaboration->posts()->value('id'),
        'destination_url' => 'https://example.com',
    ]);
});

test('the public tracking hop redirects and counts a click', function () {
    [, $collaboration] = bookedDeal();
    $link = $collaboration->trackingLinks()->first();
    $post = $collaboration->posts()->first();

    $this->get(route('tracking.redirect', $link->slug))
        ->assertRedirect();

    expect($post->metrics()->first()->clicks)->toBe(1);

    $this->get(route('tracking.redirect', $link->slug))
        ->assertRedirect();

    expect($post->metrics()->first()->fresh()->clicks)->toBe(2);
});

test('unknown tracking slugs return 404', function () {
    $this->get('/t/missing-slug')->assertNotFound();
});

test('companies cannot see another workspace tracking link', function () {
    [, $collaboration] = bookedDeal();
    $link = $collaboration->trackingLinks()->first();
    $other = User::factory()->company()->onboarded()->create();

    $this->actingAs($other)
        ->patchJson(route('api.company.tracking-links.update', $link), [
            'destination_url' => 'https://other.example',
        ])
        ->assertNotFound();
});

test('companies can add extra tracking links for a collaboration', function () {
    [$owner, $collaboration] = bookedDeal();

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.tracking-links.store', $collaboration->campaign), [
            'collaboration_id' => $collaboration->id,
            'destination_url' => 'https://example.com/demo',
            'utm_content' => 'demo-cta',
        ])
        ->assertOk()
        ->assertJsonPath('data.destination_url', 'https://example.com/demo')
        ->assertJsonPath('data.utm_content', 'demo-cta');
});

test('booking without a company website is rejected', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $owner->company->update(['website' => null]);
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
        'available_cents' => 50000,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.book', $collaboration))
        ->assertUnprocessable();

    expect($collaboration->fresh()->status)->toBe(CollaborationStatus::Selected);
});
