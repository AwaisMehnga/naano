<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class HomeRedirect
{
    /**
     * Path an authenticated user should land on.
     */
    public static function path(?Authenticatable $user): string
    {
        if (! $user instanceof User) {
            return route('login', absolute: false);
        }

        if (! $user->hasVerifiedEmail()) {
            return route('verification.notice', absolute: false);
        }

        return match ($user->side()) {
            'creator' => $user->isOnboarded()
                ? route('creator', absolute: false)
                : route('onboarding.creator', absolute: false),
            'company' => $user->isOnboarded()
                ? route('company', absolute: false)
                : route('onboarding.company', absolute: false),
            default => route('home', absolute: false),
        };
    }
}
