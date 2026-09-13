<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    public static function rule(): Password
    {
        $password = Password::min(12)
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols();

        return app()->isProduction()
            ? $password->uncompromised()
            : $password;
    }

    public static function hint(): string
    {
        return 'At least 12 characters, with upper and lower case, a number, and a symbol.';
    }
}
