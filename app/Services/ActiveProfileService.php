<?php

namespace App\Services;

use App\Enums\ProfileType;
use App\Models\User;
use Illuminate\Http\Request;

class ActiveProfileService
{
    public const SESSION_KEY = 'active_profile_type';

    public function type(Request $request): ?ProfileType
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        $header = $request->headers->get('X-Profile-Type');

        if (is_string($header) && $header !== '') {
            $type = ProfileType::tryFrom($header);

            if ($type instanceof ProfileType && $type->isAvailable() && $this->userOwns($user, $type)) {
                $request->session()->put(self::SESSION_KEY, $type->value);

                return $type;
            }
        }

        $session = $request->session()->get(self::SESSION_KEY);

        if (is_string($session)) {
            $type = ProfileType::tryFrom($session);

            if ($type instanceof ProfileType && $type->isAvailable() && $this->userOwns($user, $type)) {
                return $type;
            }
        }

        return $this->defaultType($user);
    }

    public function activate(User $user, Request $request, ProfileType $type): ProfileType
    {
        if (! $type->isAvailable() || ! $this->userOwns($user, $type)) {
            abort(422, 'That profile is not available on this account.');
        }

        $request->session()->put(self::SESSION_KEY, $type->value);

        return $type;
    }

    public function userOwns(User $user, ProfileType $type): bool
    {
        return match ($type) {
            ProfileType::Creator => $user->creatorProfile !== null,
            ProfileType::Company => $user->company !== null,
            ProfileType::Agency, ProfileType::Partner => false,
        };
    }

    public function isOnboarded(User $user, ProfileType $type): bool
    {
        return match ($type) {
            ProfileType::Creator => $user->creatorProfile?->onboarded_at !== null,
            ProfileType::Company => $user->company?->onboarded_at !== null,
            ProfileType::Agency, ProfileType::Partner => false,
        };
    }

    public function defaultType(User $user): ?ProfileType
    {
        foreach (ProfileType::activeCases() as $type) {
            if ($this->userOwns($user, $type)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @return list<array{type: string, onboarded: bool, label: string}>
     */
    public function listFor(User $user): array
    {
        $profiles = [];

        foreach (ProfileType::activeCases() as $type) {
            if (! $this->userOwns($user, $type)) {
                continue;
            }

            $profiles[] = [
                'type' => $type->value,
                'onboarded' => $this->isOnboarded($user, $type),
                'label' => match ($type) {
                    ProfileType::Creator => $user->creatorProfile?->display_name ?: $user->name,
                    ProfileType::Company => $user->company?->name ?: $user->name,
                    default => $user->name,
                },
            ];
        }

        return $profiles;
    }
}
