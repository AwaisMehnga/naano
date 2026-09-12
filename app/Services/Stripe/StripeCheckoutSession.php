<?php

namespace App\Services\Stripe;

final class StripeCheckoutSession
{
    public function __construct(
        public string $id,
        public string $url,
        public ?string $customerId = null,
    ) {}
}
