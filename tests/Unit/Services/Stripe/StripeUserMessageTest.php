<?php

use App\Services\Stripe\StripeUserMessage;

test('connect setup errors tell the user to finish the Stripe Dashboard', function () {
    $message = StripeUserMessage::from('You can only create new accounts if you\'ve signed up for Connect');

    expect($message)->toBe('Stripe Connect is not enabled. Open the Stripe Dashboard, finish Connect platform setup, then try Set up payouts again.');
});

test('country mismatch errors tell the user to update their profile country', function () {
    $message = StripeUserMessage::from('The country of this account is different from the platform country.');

    expect($message)->toBe('Payouts use the country on your creator profile, not the platform country. Update your country there, then try Set up payouts again.');
});

test('other stripe errors keep the original message', function () {
    expect(StripeUserMessage::from('Some fields in the request were invalid: dashboard'))
        ->toBe('Some fields in the request were invalid: dashboard');
});
