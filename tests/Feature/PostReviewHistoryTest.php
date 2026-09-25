<?php

use App\Enums\PostReviewAction;
use App\Enums\PostStatus;
use App\Models\PostReview;
use App\Notifications\CampaignUpdated;
use Illuminate\Support\Facades\Notification;

test('submitting a post records review history', function () {
    [, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Ready for review.',
        'status' => PostStatus::Draft,
    ]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.submit', $post))
        ->assertOk()
        ->assertJsonPath('data.reviews.0.action', 'submitted');

    expect(PostReview::query()->where('post_id', $post->id)->count())->toBe(1)
        ->and(PostReview::query()->first()?->action)->toBe(PostReviewAction::Submitted);
});

test('company decisions append review history', function () {
    [$owner, $collaboration] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Draft for review.',
        'status' => PostStatus::InReview,
        'submitted_at' => now(),
    ]);

    PostReview::query()->create([
        'post_id' => $post->id,
        'actor_user_id' => $collaboration->creatorProfile->user_id,
        'action' => PostReviewAction::Submitted,
        'note' => null,
        'created_at' => now()->subMinute(),
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.posts.changes', $post), [
            'review_note' => 'Lead with the repo.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'changes_requested');

    $show = $this->actingAs($owner)
        ->getJson(route('api.company.posts.show', $post))
        ->assertOk();

    expect($show->json('data.reviews'))->toHaveCount(2)
        ->and($show->json('data.reviews.0.action'))->toBe('changes_requested')
        ->and($show->json('data.reviews.0.note'))->toBe('Lead with the repo.');
});

test('post submit notification stays database only with deep link', function () {
    Notification::fake();

    [$owner, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();
    $post->update([
        'body' => 'Notify me.',
        'status' => PostStatus::Draft,
    ]);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.posts.submit', $post))
        ->assertOk();

    Notification::assertSentTo(
        $owner,
        CampaignUpdated::class,
        function (CampaignUpdated $notification, array $channels) use ($post, $collaboration): bool {
            return $channels === ['database']
                && $notification->mailable === false
                && $notification->toArray($collaboration->campaign->company->user)['href']
                    === '/campaigns/'.$collaboration->campaign_id.'/posts/'.$post->id;
        },
    );
});
