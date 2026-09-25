<?php

namespace App\Services;

use App\Enums\ProfileType;
use App\Models\User;
use App\Support\PublicDisk;
use Illuminate\Http\Request;

class UserService
{
    public function __construct(private ActiveProfileService $activeProfile) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     email_verified_at: mixed,
     *     avatar: string|null,
     *     role: string|null,
     *     active_profile: string|null,
     *     profiles: list<array{type: string, onboarded: bool, label: string}>,
     *     can_create_profiles: list<string>,
     *     onboarded: bool,
     *     unread_notifications_count: int
     * }
     */
    public function current(User $user, Request $request): array
    {
        $active = $this->activeProfile->type($request);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'avatar' => $this->avatarUrl($user, $active),
            'role' => $active?->value,
            'active_profile' => $active?->value,
            'profiles' => $this->activeProfile->listFor($user),
            'can_create_profiles' => $this->creatableTypes($user),
            'onboarded' => $active instanceof ProfileType
                ? $this->activeProfile->isOnboarded($user, $active)
                : false,
            'unread_notifications_count' => $user->unreadNotifications()->count(),
        ];
    }

    private function avatarUrl(User $user, ?ProfileType $active): ?string
    {
        $path = match ($active) {
            ProfileType::Creator => $user->creatorProfile?->photo_path,
            ProfileType::Company => $user->company?->logo_path,
            default => null,
        };

        return PublicDisk::url(is_string($path) ? $path : null);
    }

    /**
     * @return list<string>
     */
    private function creatableTypes(User $user): array
    {
        $types = [];

        if ($user->creatorProfile === null) {
            $types[] = ProfileType::Creator->value;
        }

        if ($user->company === null) {
            $types[] = ProfileType::Company->value;
        }

        return $types;
    }
}
