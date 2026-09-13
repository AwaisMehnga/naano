<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class AuthMail
{
    public static function once(string $key, int $seconds, callable $send): void
    {
        if (! Cache::add('auth-mail:'.$key, true, $seconds)) {
            return;
        }

        $send();
    }
}
