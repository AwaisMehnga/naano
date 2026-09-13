<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SerializeAuthMail
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = match ($request->route()?->getName()) {
            'password.email' => $this->emailLockKey('password-reset', $request),
            'register.store' => $this->emailLockKey('register', $request),
            'verification.send' => $this->userLockKey('verify', $request),
            default => null,
        };

        if ($key === null) {
            return $next($request);
        }

        return Cache::lock($key, 20)->block(10, fn () => $next($request));
    }

    private function emailLockKey(string $prefix, Request $request): ?string
    {
        $email = Str::lower(trim((string) $request->input('email')));

        if ($email === '') {
            return null;
        }

        return 'auth-mail-lock:'.$prefix.':'.$email;
    }

    private function userLockKey(string $prefix, Request $request): ?string
    {
        $userId = $request->user()?->id;

        if ($userId === null) {
            return null;
        }

        return 'auth-mail-lock:'.$prefix.':'.$userId;
    }
}
