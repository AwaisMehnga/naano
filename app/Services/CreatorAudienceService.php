<?php

namespace App\Services;

use App\Models\CreatorAudienceProfile;
use App\Models\CreatorProfile;
use App\Models\User;
use App\Services\LinkedIn\AudienceMixBuilder;

class CreatorAudienceService
{
    public function __construct(
        private CreatorProfileService $profiles,
        private AudienceMixBuilder $audienceMix,
    ) {}

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
     * Refresh snapshot from the latest LinkedIn profile + engagers data.
     *
     * @return array<string, mixed>
     */
    public function refresh(User $user): array
    {
        $profile = $this->profiles->profile($user);
        $linkedin = is_array($profile->linkedin_profile) ? $profile->linkedin_profile : [];
        $followers = is_int($linkedin['follower_count'] ?? null) ? $linkedin['follower_count'] : null;
        $connections = is_int($linkedin['connections_count'] ?? null) ? $linkedin['connections_count'] : null;
        $engagers = is_array($linkedin['engagers'] ?? null) ? $linkedin['engagers'] : null;
        $mix = $this->audienceMix->fromEngagers($engagers);

        $snapshot = $this->latest($profile);

        if ($snapshot === null) {
            $snapshot = $profile->audienceProfiles()->create([
                'network' => 'linkedin',
                'followers_count' => $followers,
                'connections_count' => $connections,
                'audience_mix' => $mix,
                'captured_at' => now(),
            ]);
        } else {
            if ($followers !== null) {
                $snapshot->followers_count = $followers;
            }

            if ($connections !== null) {
                $snapshot->connections_count = $connections;
            }

            if ($mix !== []) {
                $snapshot->audience_mix = $mix;
            }

            $snapshot->captured_at = now();
            $snapshot->save();
        }

        return $this->payload($snapshot->fresh() ?? $snapshot, $profile);
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
        $linkedin = is_array($profile?->linkedin_profile) ? $profile->linkedin_profile : [];

        $followers = $snapshot?->followers_count;
        if ($followers === null && is_int($linkedin['follower_count'] ?? null)) {
            $followers = $linkedin['follower_count'];
        }

        $connections = $snapshot?->connections_count;
        if ($connections === null && is_int($linkedin['connections_count'] ?? null)) {
            $connections = $linkedin['connections_count'];
        }

        $mix = $snapshot?->audience_mix ?? [];
        if ((! is_array($mix) || $mix === []) && is_array($linkedin['engagers'] ?? null)) {
            $mix = $this->audienceMix->fromEngagers($linkedin['engagers']);
        }

        $engagers = is_array($linkedin['engagers'] ?? null) ? $linkedin['engagers'] : null;

        return [
            'followers_count' => $followers,
            'connections_count' => $connections,
            'network' => $snapshot?->network ?? 'linkedin',
            'audience_mix' => is_array($mix) ? $mix : [],
            'engagers' => $engagers,
            'captured_at' => $snapshot?->captured_at?->toIso8601String(),
        ];
    }
}
