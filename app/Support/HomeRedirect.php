<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

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

    /**
     * Path after login or email verification, ignoring unsafe intended URLs.
     */
    public static function afterAuth(Request $request): string
    {
        $fallback = self::path($request->user());
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return $fallback;
        }

        $path = parse_url($intended, PHP_URL_PATH);

        if (! is_string($path) || ! self::intendedAllowed($request->user(), $path)) {
            return $fallback;
        }

        return $path;
    }

    private static function intendedAllowed(?Authenticatable $user, string $path): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $side = $user->side();

        $shared = str_starts_with($path, '/settings');

        return match ($side) {
            'creator' => $shared
                || str_starts_with($path, '/creator')
                || str_starts_with($path, '/onboarding/creator'),
            'company' => $shared
                || str_starts_with($path, '/company')
                || str_starts_with($path, '/onboarding/company'),
            default => false,
        };
    }
}
