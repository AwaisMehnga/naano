<?php

use App\Enums\LinkedInPostsSyncStatus;
use App\Jobs\SyncLinkedInPostsJob;
use App\Models\User;
use App\Services\Apify\ApifyClient;
use App\Services\LinkedIn\LinkedInProfilePresenter;
use Illuminate\Support\Facades\Queue;

test('linkedin profile reports posts_status ready with full engagement series', function () {
    $user = User::factory()->creator()->onboarded()->create();
    $posts = [];

    for ($i = 1; $i <= 5; $i++) {
        $posts[] = [
            'id' => (string) $i,
            'linkedin_url' => "https://www.linkedin.com/posts/{$i}",
            'content' => "Post {$i}",
            'posted_at' => sprintf('2026-09-%02dT00:00:00Z', $i),
            'likes' => $i * 10,
            'comments' => $i,
            'shares' => 0,
        ];
    }

    $user->creatorProfile->update([
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
        'linkedin_verified_at' => now(),
        'linkedin_synced_at' => now(),
        'linkedin_posts_sync_status' => LinkedInPostsSyncStatus::Ready->value,
        'linkedin_posts' => $posts,
        'linkedin_profile' => [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'headline' => 'Writer',
            'follower_count' => 100,
            'connections_count' => 50,
            'positions' => [],
            'educations' => [],
            'skills' => [],
        ],
    ]);

    $payload = app(LinkedInProfilePresenter::class)->present($user->creatorProfile->fresh());

    expect($payload['posts_status'])->toBe('ready')
        ->and($payload['posts_count'])->toBe(5)
        ->and($payload['engagement_series'])->toHaveCount(5)
        ->and($payload['engagement_series'][0]['reactions'])->toBe(10)
        ->and($payload['engagement_series'][4]['reactions'])->toBe(50);
});

test('posts sync endpoint marks syncing and queues a job', function () {
    Queue::fake();

    $user = User::factory()->creator()->onboarded()->create();
    $user->creatorProfile->update([
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
        'linkedin_verified_at' => now(),
        'linkedin_posts' => [],
        'linkedin_posts_sync_status' => LinkedInPostsSyncStatus::Idle->value,
    ]);

    // Posts sync is deferred onto the queue after the response.
    $this->actingAs($user)
        ->postJson(route('api.creator.linkedin.posts.sync'))
        ->assertOk()
        ->assertJsonPath('data.posts_status', 'syncing');

    expect($user->creatorProfile->fresh()->linkedin_posts_sync_status)
        ->toBe(LinkedInPostsSyncStatus::Syncing->value);

    Queue::assertPushed(SyncLinkedInPostsJob::class, function (SyncLinkedInPostsJob $job) use ($user): bool {
        return $job->creatorProfileId === $user->creatorProfile->id;
    });
});

test('posts sync requires a verified linkedin profile', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->postJson(route('api.creator.linkedin.posts.sync'))
        ->assertStatus(422);
});

test('creator dashboard overview starts deferred linkedin posts sync', function () {
    Queue::fake();

    $user = User::factory()->creator()->onboarded()->create();
    $user->creatorProfile->update([
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
        'linkedin_verified_at' => now(),
        'linkedin_posts' => [],
        'linkedin_posts_sync_status' => LinkedInPostsSyncStatus::Idle->value,
    ]);

    $this->actingAs($user)
        ->getJson(route('api.creator.analytics.overview'))
        ->assertOk();

    expect($user->creatorProfile->fresh()->linkedin_posts_sync_status)
        ->toBe(LinkedInPostsSyncStatus::Syncing->value);

    Queue::assertPushed(SyncLinkedInPostsJob::class, function (SyncLinkedInPostsJob $job) use ($user): bool {
        return $job->creatorProfileId === $user->creatorProfile->id;
    });
});

test('linkedin verify does not start posts sync', function () {
    Queue::fake();

    $user = User::factory()->creator()->onboarded()->create();
    $code = 'ABCDEFGH';
    $user->creatorProfile->update([
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
        'linkedin_verify_code' => $code,
        'linkedin_posts_sync_status' => LinkedInPostsSyncStatus::Idle->value,
    ]);

    $this->mock(ApifyClient::class, function ($mock) use ($code): void {
        $mock->shouldReceive('runActor')->once()->andReturn([
            [
                'firstName' => 'Ada',
                'lastName' => 'Lovelace',
                'headline' => "Writer {$code}",
                'publicIdentifier' => 'ada',
                'followerCount' => 10,
                'connectionsCount' => 5,
                'countryCode' => 'FR',
            ],
        ]);
    });

    $this->actingAs($user)
        ->postJson(route('api.creator.linkedin.verify'))
        ->assertOk()
        ->assertJsonPath('data.verified', true);

    $profile = $user->creatorProfile->fresh();

    expect($profile->linkedin_posts_sync_status)->toBe(LinkedInPostsSyncStatus::Idle->value)
        ->and($profile->linkedin_posts ?? [])->toBe([]);

    Queue::assertNotPushed(SyncLinkedInPostsJob::class);
});
