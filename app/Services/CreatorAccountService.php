<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreatorAccountService
{
    public function __construct(private CreatorProfileService $profiles) {}

    /**
     * @return array<string, mixed>
     */
    public function billing(User $user): array
    {
        $profile = $this->profiles->profile($user);

        return [
            'stripe_connect_id' => $profile->stripe_connect_id,
            'payouts_enabled' => false,
            'bank_summary' => null,
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
