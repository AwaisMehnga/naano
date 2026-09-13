<?php

use App\Services\Stripe\StripeNotice;

test('stripe php user warnings are ignored', function () {
    expect(StripeNotice::shouldIgnore(E_USER_WARNING, '/vendor/stripe/stripe-php/lib/ApiRequestor.php'))->toBeTrue();
});

test('other php warnings are not ignored', function () {
    expect(StripeNotice::shouldIgnore(E_USER_WARNING, __FILE__))->toBeFalse()
        ->and(StripeNotice::shouldIgnore(E_WARNING, '/vendor/stripe/stripe-php/lib/ApiRequestor.php'))->toBeFalse();
});

test('ignore returns the callback result', function () {
    expect(StripeNotice::ignore(fn (): string => 'ok'))->toBe('ok');
});
