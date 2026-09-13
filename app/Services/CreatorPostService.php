<?php

namespace App\Services;

use App\Enums\CollaborationStatus;
use App\Enums\PostStatus;
use App\Models\Collaboration;
use App\Models\Post;
use App\Models\TrackingLink;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreatorPostService
{
    public function __construct(private TrackingLinkService $tracking) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function index(User $user, Collaboration $collaboration): array
    {
        $this->ensureOwned($user, $collaboration);

        return array_values($collaboration->posts()
            ->orderBy('id')
            ->get()
            ->map(fn (Post $post): array => $this->payload($post))
            ->all());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(User $user, Collaboration $collaboration, array $data): array
    {
        $this->ensureOwned($user, $collaboration);

        if ($collaboration->status !== CollaborationStatus::Booked) {
            throw ValidationException::withMessages([
                'status' => 'Posts can only be created on a booked collaboration.',
            ]);
        }

        $cap = max(1, (int) ($collaboration->booked_posts_count ?? 1));

        if ($collaboration->posts()->count() >= $cap) {
            throw ValidationException::withMessages([
                'posts' => 'This deal already has the booked number of posts.',
            ]);
        }

        $post = $collaboration->posts()->create([
            'status' => PostStatus::Draft,
            'body' => $data['body'] ?? null,
        ]);

        return $this->payload($post);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(User $user, Post $post, array $data): array
    {
        $this->ensurePostOwned($user, $post);

        if (! in_array($post->status, [PostStatus::Draft, PostStatus::ChangesRequested], true)) {
            throw ValidationException::withMessages([
                'status' => 'This post can no longer be edited.',
            ]);
        }

        $post->body = $data['body'];
        $post->save();

        return $this->payload($post->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function submit(User $user, Post $post): array
    {
        $this->ensurePostOwned($user, $post);

        if (! in_array($post->status, [PostStatus::Draft, PostStatus::ChangesRequested], true)) {
            throw ValidationException::withMessages([
                'status' => 'This post cannot be submitted.',
            ]);
        }

        if (! is_string($post->body) || trim($post->body) === '') {
            throw ValidationException::withMessages([
                'body' => 'Add copy before submitting this post.',
            ]);
        }

        $post->status = PostStatus::InReview;
        $post->submitted_at = now();
        $post->save();

        return $this->payload($post->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function schedule(User $user, Post $post, string $scheduledAt): array
    {
        $this->ensurePostOwned($user, $post);

        if ($post->status !== PostStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => 'Only approved posts can be scheduled.',
            ]);
        }

        $post->status = PostStatus::Scheduled;
        $post->scheduled_at = Carbon::parse($scheduledAt);
        $post->save();

        return $this->payload($post->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function publish(User $user, Post $post, string $publishedUrl, ?string $linkedinPostId = null): array
    {
        $this->ensurePostOwned($user, $post);

        if (! in_array($post->status, [PostStatus::Approved, PostStatus::Scheduled], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only approved posts can be published.',
            ]);
        }

        $host = parse_url($publishedUrl, PHP_URL_HOST);

        if (! is_string($host) || ! in_array($host, ['linkedin.com', 'www.linkedin.com'], true)) {
            throw ValidationException::withMessages([
                'published_url' => 'The live URL must be a LinkedIn post.',
            ]);
        }

        $post->status = PostStatus::Published;
        $post->published_url = $publishedUrl;
        $post->linkedin_post_id = $this->linkedinPostId($publishedUrl, $linkedinPostId);
        $post->published_at = now();
        $post->save();

        return $this->payload($post->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Post $post): array
    {
        $post->loadMissing('collaboration.trackingLinks');

        return [
            'id' => $post->id,
            'collaboration_id' => $post->collaboration_id,
            'status' => $post->status->value,
            'body' => $post->body,
            'review_note' => $post->review_note,
            'scheduled_at' => $post->scheduled_at?->toIso8601String(),
            'published_at' => $post->published_at?->toIso8601String(),
            'published_url' => $post->published_url,
            'linkedin_post_id' => $post->linkedin_post_id,
            'submitted_at' => $post->submitted_at?->toIso8601String(),
            'tracking_links' => $post->collaboration->trackingLinks
                ->map(fn (TrackingLink $link): array => $this->tracking->payload($link))
                ->values()
                ->all(),
        ];
    }

    private function linkedinPostId(string $url, ?string $explicit): ?string
    {
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        if (preg_match('/urn:li:activity:(\d+)/', $url, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/activity[:\-](\d+)/', $url, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function ensureOwned(User $user, Collaboration $collaboration): void
    {
        $profile = $user->creatorProfile;

        if ($profile === null || $collaboration->creator_profile_id !== $profile->id) {
            abort(404);
        }
    }

    private function ensurePostOwned(User $user, Post $post): void
    {
        $post->loadMissing('collaboration');
        $this->ensureOwned($user, $post->collaboration);
    }
}
