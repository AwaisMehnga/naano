<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyAccess;
use App\Support\PublicDisk;
use Illuminate\Http\Request;

class UserService
{
    public function __construct(private CurrentCompanyService $currentCompany) {}

    /**
     * @return array{id: int, name: string, email: string, email_verified_at: mixed, avatar: string|null, role: string|null, onboarded: bool, current_company_id: int|null, membership_role: string|null, unread_notifications_count: int}
     */
    public function current(User $user, Request $request): array
    {
        $company = $user->side() === 'company' ? $this->currentCompany->resolve($request) : null;
        $role = $company instanceof Company ? CompanyAccess::role($user, $company) : null;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'avatar' => $this->avatarUrl($user, $company),
            'role' => $user->side(),
            'onboarded' => $user->isOnboarded(),
            'current_company_id' => $company?->id,
            'membership_role' => $role?->value,
            'unread_notifications_count' => $user->unreadNotifications()->count(),
        ];
    }

    private function avatarUrl(User $user, ?Company $company): ?string
    {
        $path = match ($user->side()) {
            'creator' => $user->creatorProfile?->photo_path,
            'company' => $company?->logo_path,
            default => null,
        };

        return PublicDisk::url(is_string($path) ? $path : null);
    }
}
