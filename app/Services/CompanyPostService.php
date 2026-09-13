<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Collaboration;
use App\Models\Company;
use App\Models\Post;
use App\Models\TrackingLink;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CompanyPostService
{
    public function __construct(
        private TrackingLinkService $tracking,
        private CollaborationNotifier $notifier,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function index(Company $company, Collaboration $collaboration): array
    {
        $this->ensureCollaborationOwned($company, $collaboration);

        return array_values($collaboration->posts()
            ->orderBy('id')
            ->get()
            ->map(fn (Post $post): array => $this->payload($company, $post))
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function show(Company $company, Post $post): array
    {
        $this->ensurePostOwned($company, $post);

        return $this->payload($company, $post);
    }

    /**
     * @return array<string, mixed>
     */
    public function approve(Company $company, User $actor, Post $post): array
    {
        $this->ensurePostOwned($company, $post);
        $this->assertInReview($post);

        $post->status = PostStatus::Approved;
        $post->reviewed_at = now();
        $post->reviewed_by_user_id = $actor->id;
        $post->save();

        $this->notifier->postApproved($post, $actor);

        return $this->payload($company, $post->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function requestChanges(Company $company, User $actor, Post $post, string $reviewNote): array
    {
        $this->ensurePostOwned($company, $post);
        $this->assertInReview($post);

        $post->status = PostStatus::ChangesRequested;
        $post->review_note = $reviewNote;
        $post->reviewed_at = now();
        $post->reviewed_by_user_id = $actor->id;
        $post->save();

        $this->notifier->postChangesRequested($post, $actor);

        return $this->payload($company, $post->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function reject(Company $company, User $actor, Post $post, ?string $reviewNote): array
    {
        $this->ensurePostOwned($company, $post);
        $this->assertInReview($post);

        $post->status = PostStatus::Rejected;
        $post->review_note = $reviewNote;
        $post->reviewed_at = now();
        $post->reviewed_by_user_id = $actor->id;
        $post->save();

        $this->notifier->postRejected($post, $actor);

        return $this->payload($company, $post->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Company $company, Post $post): array
    {
        $post->loadMissing(['collaboration.campaign', 'collaboration.trackingLinks']);
        $campaign = $post->collaboration->campaign;

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
            'guidelines' => $campaign->guidelines,
            'tracking_links' => $post->collaboration->trackingLinks
                ->map(fn (TrackingLink $link): array => $this->tracking->payload($link))
                ->values()
                ->all(),
        ];
    }

    private function assertInReview(Post $post): void
    {
        if ($post->status !== PostStatus::InReview) {
            throw ValidationException::withMessages([
                'status' => 'Only posts in review can be decided.',
            ]);
        }
    }

    private function ensureCollaborationOwned(Company $company, Collaboration $collaboration): void
    {
        $collaboration->loadMissing('campaign');

        if ($collaboration->campaign->company_id !== $company->id) {
            abort(404);
        }
    }

    private function ensurePostOwned(Company $company, Post $post): void
    {
        $post->loadMissing('collaboration.campaign');
        $this->ensureCollaborationOwned($company, $post->collaboration);
    }
}
