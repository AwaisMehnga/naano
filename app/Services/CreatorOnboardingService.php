<?php

namespace App\Services;

use App\Enums\CreatorVettingStatus;
use App\Models\CreatorProfile;
use App\Models\User;
use App\Services\LinkedIn\LinkedInVerificationService;
use Illuminate\Http\UploadedFile;

class CreatorOnboardingService
{
    public function __construct(private LinkedInVerificationService $linkedin) {}

    public function profile(User $user): CreatorProfile
    {
        return $user->creatorProfile()->firstOrCreate([]);
    }

    public function step(CreatorProfile $profile): string
    {
        if (! $profile->isLinkedInVerified() || $profile->linkedin_url === null || $profile->country === null) {
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
     * @param  array{linkedin_url: string, country: string, photo?: UploadedFile|null}  $data
     * @return array{verify_code: string, linkedin_url: string}
     */
    public function startLinkedIn(CreatorProfile $profile, array $data): array
    {
        $photoPath = $profile->photo_path;

        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            $photoPath = $data['photo']->store('creator-photos', 'public');
        }

        $result = $this->linkedin->start($profile, $data['linkedin_url']);

        $profile->update([
            'country' => $data['country'],
            'photo_path' => $photoPath,
        ]);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyLinkedIn(CreatorProfile $profile): array
    {
        return $this->linkedin->verify($profile);
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
