<?php

namespace App\Services\Stripe;

class StripeNotice
{
    public static function shouldIgnore(int $severity, string $file): bool
    {
        return $severity === E_USER_WARNING && str_contains($file, 'stripe-php');
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function ignore(callable $callback): mixed
    {
        set_error_handler(function (int $severity, string $message, string $file): bool {
            return self::shouldIgnore($severity, $file);
        });

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
