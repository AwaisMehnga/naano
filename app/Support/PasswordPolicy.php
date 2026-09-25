<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    public static function rule(): Password
    {
        return Password::min(8);
    }

    public static function hint(): string
    {
        return 'At least 8 characters.';
    }
}
