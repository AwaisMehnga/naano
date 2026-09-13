<?php

namespace App\Services\Stripe;

class StripeUserMessage
{
    public static function from(string $message): string
    {
        if (
            str_contains($message, 'signed up for Connect')
            || str_contains($message, 'Accounts v2 is not enabled')
            || str_contains($message, 'platform-setup')
            || str_contains($message, 'non_connect_platform')
        ) {
            return 'Stripe Connect is not enabled. Open the Stripe Dashboard, finish Connect platform setup, then try Set up payouts again.';
        }

        if (
            str_contains($message, 'country')
            && (
                str_contains($message, 'different')
                || str_contains($message, 'match')
                || str_contains($message, 'service agreement')
            )
        ) {
            return 'Payouts use the country on your creator profile, not the platform country. Update your country there, then try Set up payouts again.';
        }

        return $message;
    }
}
