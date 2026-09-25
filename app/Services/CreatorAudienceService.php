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

        return $this->payload($snapshot, $profile);
    }

    /**
     * @return array<string, mixed>
     */
    public function refresh(User $user): array
    {
        $profile = $this->profiles->profile($user);
        $followers = is_array($profile->linkedin_profile)
            ? ($profile->linkedin_profile['follower_count'] ?? null)
            : null;

        $snapshot = $this->latest($profile);

        if ($snapshot === null) {
            $snapshot = $profile->audienceProfiles()->create([
                'network' => 'linkedin',
                'followers_count' => is_int($followers) ? $followers : null,
                'audience_mix' => [],
                'captured_at' => now(),
            ]);
        } else {
            if (is_int($followers)) {
                $snapshot->followers_count = $followers;
            }

            $snapshot->captured_at = now();
            $snapshot->save();
        }

        return $this->payload($snapshot, $profile);
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
    private function payload(?CreatorAudienceProfile $snapshot, ?CreatorProfile $profile = null): array
    {
        $followers = $snapshot?->followers_count;

        if ($followers === null && is_array($profile?->linkedin_profile)) {
            $fromProfile = $profile->linkedin_profile['follower_count'] ?? null;
            $followers = is_int($fromProfile) ? $fromProfile : null;
        }

        return [
            'followers_count' => $followers,
            'network' => $snapshot?->network ?? 'linkedin',
            'audience_mix' => $snapshot?->audience_mix ?? [],
            'captured_at' => $snapshot?->captured_at?->toIso8601String(),
        ];
    }
}
