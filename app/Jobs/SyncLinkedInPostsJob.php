<?php

namespace App\Jobs;

use App\Models\CreatorProfile;
use App\Services\LinkedIn\LinkedInSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncLinkedInPostsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $creatorProfileId) {}

    public function handle(LinkedInSyncService $sync): void
    {
        $profile = CreatorProfile::query()->find($this->creatorProfileId);

        if (! $profile instanceof CreatorProfile) {
            return;
        }

        $sync->syncPosts($profile);
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('SyncLinkedInPostsJob failed', [
            'creator_profile_id' => $this->creatorProfileId,
            'message' => $exception?->getMessage(),
        ]);
    }
}
