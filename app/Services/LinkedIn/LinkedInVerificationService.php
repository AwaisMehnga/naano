<?php

namespace App\Services\LinkedIn;

use App\Models\CreatorProfile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LinkedInVerificationService
{
    public function __construct(
        private LinkedInSyncService $sync,
    ) {}

    /**
     * Save LinkedIn URL and issue an ownership challenge code.
     *
     * @return array{verify_code: string, linkedin_url: string}
     */
    public function start(CreatorProfile $profile, string $linkedinUrl): array
    {
        $code = $this->generateCode();

        $profile->update([
            'linkedin_url' => $linkedinUrl,
            'linkedin_verify_code' => $code,
            'linkedin_verified_at' => null,
        ]);

        return [
            'verify_code' => $code,
            'linkedin_url' => $linkedinUrl,
        ];
    }

    /**
     * Scrape the profile and confirm the headline contains the verify code.
     *
     * @return array<string, mixed>
     */
    public function verify(CreatorProfile $profile): array
    {
        if (! is_string($profile->linkedin_url) || $profile->linkedin_url === '') {
            throw ValidationException::withMessages([
                'linkedin_url' => 'Add your LinkedIn URL before verifying.',
            ]);
        }

        if (! is_string($profile->linkedin_verify_code) || $profile->linkedin_verify_code === '') {
            throw ValidationException::withMessages([
                'linkedin' => 'Start verification to get a code first.',
            ]);
        }

        try {
            $normalized = $this->sync->scrapeProfile($profile->linkedin_url);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'linkedin' => 'Could not fetch your LinkedIn profile. Try again in a moment.',
            ]);
        }

        $headline = (string) ($normalized['headline'] ?? '');
        $code = $profile->linkedin_verify_code;

        if (! str_contains(Str::upper($headline), Str::upper($code))) {
            throw ValidationException::withMessages([
                'linkedin' => 'We could not find your verification code at the end of your LinkedIn headline. Add the code after your headline, save on LinkedIn, then try again.',
            ]);
        }

        $this->sync->storeVerifiedProfile($profile, $normalized);

        $fresh = $profile->fresh() ?? $profile;

        return app(LinkedInProfilePresenter::class)->present($fresh);
    }

    private function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }
}
