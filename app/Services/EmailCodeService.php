<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use App\Support\AuthMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class EmailCodeService
{
    public function send(User $user): void
    {
        AuthMail::once('verify:'.$user->id, 15, function () use ($user): void {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            Cache::put($this->key($user), Hash::make($code), now()->addMinutes(15));

            $user->notify(new EmailVerificationCode($code));
        });
    }

    public function verify(User $user, string $code): bool
    {
        $hash = Cache::get($this->key($user));

        if (! is_string($hash) || ! Hash::check($code, $hash)) {
            return false;
        }

        $user->markEmailAsVerified();
        Cache::forget($this->key($user));

        return true;
    }

    private function key(User $user): string
    {
        return 'email-otp:'.$user->id;
    }
}
