<?php

namespace App\Services;

use App\Enums\CreatorVettingStatus;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class CreatorOnboardingService
{
    public function profile(User $user): CreatorProfile
    {
        return $user->creatorProfile()->firstOrCreate([]);
    }

    public function step(CreatorProfile $profile): string
    {
        if ($profile->linkedin_url === null || $profile->headline === null || $profile->country === null) {
            return 'linkedin';
        }

        if ($profile->industries === null || $profile->industries === []) {
            return 'industries';
        }

        if ($profile->price_cents === null) {
            return 'offer';
        }

        return 'professional';
    }

    /**
     * @param  array{linkedin_url: string, headline: string, country: string, photo?: UploadedFile|null}  $data
     */
    public function saveLinkedIn(CreatorProfile $profile, array $data): void
    {
        $photoPath = $profile->photo_path;

        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            $photoPath = $data['photo']->store('creator-photos', 'public');
        }

        $profile->update([
            'linkedin_url' => $data['linkedin_url'],
            'headline' => $data['headline'],
            'country' => $data['country'],
            'photo_path' => $photoPath,
        ]);
    }

    /**
     * @param  list<string>  $industries
     */
    public function saveIndustries(CreatorProfile $profile, array $industries): void
    {
        $profile->update([
            'industries' => $industries,
        ]);
    }

    /**
     * @param  array{price_cents: int, bundles: list<array{posts: int, total_cents: int}>}  $data
     */
    public function saveOffer(CreatorProfile $profile, array $data): void
    {
        $profile->update([
            'price_cents' => $data['price_cents'],
            'bundles' => $data['bundles'],
        ]);
    }

    public function complete(CreatorProfile $profile): void
    {
        $profile->update([
            'onboarded_at' => now(),
            'vetting_status' => CreatorVettingStatus::Vetted,
        ]);
    }
}
