<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;

class CompanyAccess
{
    public static function canManageMoney(User $user, Company $company): bool
    {
        return $company->user_id === $user->id;
    }

    public static function ensureCanManageMoney(User $user, Company $company): void
    {
        if (! self::canManageMoney($user, $company)) {
            abort(403, 'Only the company owner can manage campaign funds.');
        }
    }

    public static function owns(User $user, Company $company): bool
    {
        return $company->user_id === $user->id;
    }

    public static function ensureOwns(User $user, Company $company): void
    {
        if (! self::owns($user, $company)) {
            abort(403, 'You do not own this company.');
        }
    }
}
