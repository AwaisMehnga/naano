<?php

use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Enums\PostStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\User;

test('creators can list drafts created when the company books', function () {
    [, $collaboration, $creatorUser] = bookedDeal();

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.collaborations.posts.index', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.0.status', 'draft');
});

test('creators can create another draft until the booked post count', function () {
    [, $collaboration, $creatorUser] = bookedDeal();
    $collaboration->update(['booked_posts_count' => 2]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.collaborations.posts.store', $collaboration), [
            'body' => 'Draft copy in my voice.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.body', 'Draft copy in my voice.');
});

test('creators cannot create more drafts than the booked post count', function () {
    [, $collaboration, $creatorUser] = bookedDeal();

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.collaborations.posts.store', $collaboration))
        ->assertUnprocessable();
});

test('creators cannot create posts on an invited collaboration', function () {
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
        'source' => CollaborationSource::Invite,
    ]);

    $this->actingAs($creator->user)
        ->postJson(route('api.creator.collaborations.posts.store', $collaboration), [
            'body' => 'Too early.',
        ])
        ->assertUnprocessable();
});

test('creators can edit a draft and submit it for review', function () {
    [, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();

    $this->actingAs($creatorUser)
        ->patchJson(route('api.creator.posts.update', $post), [
            'body' => 'Anyone can say they ship. Few show the repo.',
        ])
        ->assertOk()
        ->assertJsonPath('data.body', 'Anyone can say they ship. Few show the repo.');

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.submit', $post))
        ->assertOk()
        ->assertJsonPath('data.status', 'in_review');
});

test('creators cannot submit an empty draft', function () {
    [, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.submit', $post))
        ->assertUnprocessable();
});

test('creators cannot publish a draft without approval', function () {
    [, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.publish', $post), [
            'published_url' => 'https://www.linkedin.com/posts/ada-1',
        ])
        ->assertUnprocessable();
});

test('creators can schedule and publish an approved post', function () {
    [$owner, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Live proof belongs on LinkedIn.',
        'status' => PostStatus::Approved,
    ]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.schedule', $post), [
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'scheduled');

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.publish', $post), [
            'published_url' => 'https://www.linkedin.com/feed/update/urn:li:activity:1234567890',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'published')
        ->assertJsonPath('data.linkedin_post_id', '1234567890');

    expect($owner->company->wallet->fresh()->available_cents)->toBe(26000);
});

test('creators cannot see another creator post', function () {
    [, $collaboration] = bookedDeal();
    $other = marketplaceCreator(['display_name' => 'Other Ada']);
    $post = $collaboration->posts()->first();

    $this->actingAs($other->user)
        ->getJson(route('api.creator.collaborations.posts.index', $collaboration))
        ->assertNotFound();

    $this->actingAs($other->user)
        ->patchJson(route('api.creator.posts.update', $post), ['body' => 'Nope'])
        ->assertNotFound();
});
