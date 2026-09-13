<?php

namespace App\Services;

use App\Models\User;
use App\Services\Stripe\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreatorAccountService
{
    public function __construct(
        private CreatorProfileService $profiles,
        private StripeGateway $stripe,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function billing(User $user): array
    {
        $profile = $this->profiles->profile($user);

        return [
            'stripe_connect_id' => $profile->stripe_connect_id,
            'payouts_enabled' => $profile->payouts_enabled,
            'bank_summary' => null,
        ];
    }

    /**
     * @return array{onboarded: bool, payouts_enabled: bool, stripe_connect_id: string|null}
     */
    public function connect(User $user): array
    {
        $profile = $this->profiles->profile($user);

        return [
            'onboarded' => is_string($profile->stripe_connect_id) && $profile->stripe_connect_id !== '',
            'payouts_enabled' => $profile->payouts_enabled,
            'stripe_connect_id' => $profile->stripe_connect_id,
        ];
    }

    /**
     * @return array{url: string}
     */
    public function startOnboarding(User $user): array
    {
        $profile = $this->profiles->profile($user);

        if (! is_string($profile->stripe_connect_id) || $profile->stripe_connect_id === '') {
            $profile->stripe_connect_id = $this->stripe->createConnectAccount($profile, $user->email);
            $profile->save();
        }

        return [
            'url' => $this->stripe->createAccountLink(
                $profile->stripe_connect_id,
                url('/creator/setting/billing?connect=refresh'),
                url('/creator/setting/billing?connect=return'),
            ),
        ];
    }

    /**
     * @return array{url: string}
     */
    public function dashboard(User $user): array
    {
        $profile = $this->profiles->profile($user);

        if (! is_string($profile->stripe_connect_id) || $profile->stripe_connect_id === '') {
            throw ValidationException::withMessages([
                'stripe_connect_id' => 'Connect onboarding has not started.',
            ]);
        }

        return [
            'url' => $this->stripe->createLoginLink($profile->stripe_connect_id),
        ];
    }

    public function destroy(User $user, Request $request): void
    {
        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
