<?php

namespace App\Services;

use App\Models\CreatorAudienceProfile;
use App\Models\CreatorProfile;
use App\Models\User;

class CreatorAudienceService
{
    public function __construct(private CreatorProfileService $profiles) {}

    /**
     * @return array<string, mixed>
     */
    public function show(User $user): array
    {
        $profile = $this->profiles->profile($user);
        $snapshot = $this->latest($profile);

        return $this->payload($snapshot);
    }

    /**
     * @return array<string, mixed>
     */
    public function refresh(User $user): array
    {
        $profile = $this->profiles->profile($user);
        $snapshot = $this->latest($profile);

        if ($snapshot === null) {
            $snapshot = $profile->audienceProfiles()->create([
                'network' => 'linkedin',
                'followers_count' => null,
                'audience_mix' => [],
                'captured_at' => now(),
            ]);
        } else {
            $snapshot->captured_at = now();
            $snapshot->save();
        }

        return $this->payload($snapshot);
    }

    private function latest(CreatorProfile $profile): ?CreatorAudienceProfile
    {
        return $profile->audienceProfiles()
            ->orderByDesc('captured_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(?CreatorAudienceProfile $snapshot): array
    {
        return [
            'followers_count' => $snapshot?->followers_count,
            'network' => $snapshot?->network ?? 'linkedin',
            'audience_mix' => $snapshot?->audience_mix ?? [],
            'captured_at' => $snapshot?->captured_at?->toIso8601String(),
        ];
    }
}
