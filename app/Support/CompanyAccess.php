<?php

namespace App\Support;

use App\Enums\CompanyMemberRole;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;

class CompanyAccess
{
    public static function canManageMoney(User $user, Company $company): bool
    {
        return self::role($user, $company) === CompanyMemberRole::Owner;
    }

    public static function ensureCanManageMoney(User $user, Company $company): void
    {
        if (! self::canManageMoney($user, $company)) {
            abort(403, 'Only owners can manage campaign funds.');
        }
    }

    public static function role(User $user, Company $company): ?CompanyMemberRole
    {
        $membership = CompanyMember::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->first();

        return $membership?->role;
    }

    public static function ownerCount(Company $company): int
    {
        return CompanyMember::query()
            ->where('company_id', $company->id)
            ->where('role', CompanyMemberRole::Owner)
            ->count();
    }
}
