<?php

use App\Enums\PostStatus;
use App\Models\User;

test('members can approve a submitted post', function () {
    [$owner, $company, $member] = companyWithMember();
    [, $collaboration] = bookedDeal($owner);
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Draft for review.',
        'status' => PostStatus::InReview,
        'submitted_at' => now(),
    ]);

    $this->actingAs($member)
        ->withHeaders(['X-Company-Id' => (string) $company->id])
        ->postJson(route('api.company.posts.approve', $post))
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');
});

test('creators cannot use company review routes', function () {
    [, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();

    $this->actingAs($creatorUser)
        ->postJson(route('api.company.posts.approve', $post))
        ->assertForbidden();
});

test('companies can request changes and the creator can resubmit', function () {
    [$owner, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'First draft.',
        'status' => PostStatus::InReview,
        'submitted_at' => now(),
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.posts.changes', $post), [
            'review_note' => 'Lead with the repo, not the slogan.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'changes_requested')
        ->assertJsonPath('data.review_note', 'Lead with the repo, not the slogan.');

    $this->actingAs($creatorUser)
        ->patchJson(route('api.creator.posts.update', $post), [
            'body' => 'Here is the repo. Then the claim.',
        ])
        ->assertOk();

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.submit', $post))
        ->assertOk()
        ->assertJsonPath('data.status', 'in_review');
});

test('rejected posts cannot be edited or submitted', function () {
    [$owner, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Off brief.',
        'status' => PostStatus::InReview,
        'submitted_at' => now(),
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.posts.reject', $post), [
            'review_note' => 'Wrong campaign.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    $this->actingAs($creatorUser)
        ->patchJson(route('api.creator.posts.update', $post), ['body' => 'Retry'])
        ->assertUnprocessable();

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.submit', $post))
        ->assertUnprocessable();
});

test('publishing a post does not capture the wallet hold', function () {
    [$owner, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Shipped.',
        'status' => PostStatus::Approved,
    ]);

    $available = $owner->company->wallet->fresh()->available_cents;
    $held = $owner->company->wallet->fresh()->transactions()
        ->where('type', 'hold')
        ->where('status', 'posted')
        ->sum('amount_cents');

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.publish', $post), [
            'published_url' => 'https://www.linkedin.com/posts/ada-live',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    expect($owner->company->wallet->fresh()->available_cents)->toBe($available);

    $this->assertDatabaseMissing('wallet_transactions', [
        'collaboration_id' => $collaboration->id,
        'type' => 'capture',
    ]);

    expect($held)->toBe(24000);
});

test('companies cannot review another workspace post', function () {
    [, $collaboration] = bookedDeal();
    $other = User::factory()->company()->onboarded()->create();
    $post = $collaboration->posts()->first();

    $this->actingAs($other)
        ->getJson(route('api.company.posts.show', $post))
        ->assertNotFound();
});
