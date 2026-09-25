<?php

use App\Enums\PostStatus;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\CampaignUpdated;
use Illuminate\Support\Facades\Notification;

test('owners can approve a submitted post', function () {
    [$owner, $collaboration] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Draft for review.',
        'status' => PostStatus::InReview,
        'submitted_at' => now(),
    ]);

    $this->actingAs($owner)
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

test('publishing the last post captures the wallet hold', function () {
    [$owner, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Shipped.',
        'status' => PostStatus::Approved,
    ]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.publish', $post), [
            'published_url' => 'https://www.linkedin.com/posts/ada-live',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    $this->assertDatabaseHas('wallet_transactions', [
        'collaboration_id' => $collaboration->id,
        'type' => 'capture',
        'status' => 'posted',
    ]);
});

test('companies cannot review another workspace post', function () {
    [, $collaboration] = bookedDeal();
    $other = User::factory()->company()->onboarded()->create();
    $post = $collaboration->posts()->first();

    $this->actingAs($other)
        ->getJson(route('api.company.posts.show', $post))
        ->assertNotFound();
});

test('submitting a post notifies the company in-app with a review deep link and no mail', function () {
    Notification::fake();

    [$owner, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Ready for review.',
        'status' => PostStatus::Draft,
    ]);

    NotificationPreference::factory()->create([
        'user_id' => $owner->id,
        'email_campaign_updates' => true,
    ]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.submit', $post))
        ->assertOk()
        ->assertJsonPath('data.status', 'in_review');

    Notification::assertSentTo(
        $owner,
        CampaignUpdated::class,
        function (CampaignUpdated $notification, array $channels) use ($post, $collaboration, $owner): bool {
            $payload = $notification->toArray($owner);

            return $channels === ['database']
                && $notification->mailable === false
                && $notification->postId === $post->id
                && $payload['href'] === '/campaigns/'.$collaboration->campaign_id.'/posts/'.$post->id;
        },
    );
});

test('company post show includes the creator', function () {
    [$owner, $collaboration] = bookedDeal();
    $post = $collaboration->posts()->first();
    $profile = $collaboration->creatorProfile;

    $this->actingAs($owner)
        ->getJson(route('api.company.posts.show', $post))
        ->assertOk()
        ->assertJsonPath('data.creator.id', $profile->id)
        ->assertJsonPath('data.creator.display_name', $profile->display_name);
});
