<?php

namespace App\Support;

use App\Enums\ProfileType;
use App\Models\User;
use App\Services\ActiveProfileService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class HomeRedirect
{
    /**
     * Path an authenticated user should land on.
     */
    public static function path(?Authenticatable $user, ?Request $request = null): string
    {
        if (! $user instanceof User) {
            return route('login', absolute: false);
        }

        if (! $user->hasVerifiedEmail()) {
            return route('verification.notice', absolute: false);
        }

        $request ??= request();
        $activeProfile = app(ActiveProfileService::class);
        $type = $activeProfile->type($request) ?? $activeProfile->defaultType($user);

        if (! $type instanceof ProfileType) {
            return route('profiles.choose', absolute: false);
        }

        return match ($type) {
            ProfileType::Creator => $activeProfile->isOnboarded($user, $type)
                ? route('creator', absolute: false)
                : route('onboarding.creator', absolute: false),
            ProfileType::Company => $activeProfile->isOnboarded($user, $type)
                ? route('company', absolute: false)
                : route('onboarding.company', absolute: false),
            default => route('profiles.choose', absolute: false),
        };
    }

    /**
     * Path after login or email verification, ignoring unsafe intended URLs.
     */
    public static function afterAuth(Request $request): string
    {
        $fallback = self::path($request->user(), $request);
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return $fallback;
        }

        $path = parse_url($intended, PHP_URL_PATH);

        if (! is_string($path) || ! self::intendedAllowed($request->user(), $path, $request)) {
            return $fallback;
        }

        return $path;
    }

    private static function intendedAllowed(?Authenticatable $user, string $path, Request $request): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $type = app(ActiveProfileService::class)->type($request)
            ?? app(ActiveProfileService::class)->defaultType($user);

        $shared = str_starts_with($path, '/settings') || str_starts_with($path, '/profiles');

        return match ($type) {
            ProfileType::Creator => $shared
                || str_starts_with($path, '/creator')
                || str_starts_with($path, '/onboarding/creator'),
            ProfileType::Company => $shared
                || str_starts_with($path, '/company')
                || str_starts_with($path, '/onboarding/company'),
            default => $shared,
        };
    }
}
