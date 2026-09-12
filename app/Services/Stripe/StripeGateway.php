<?php

namespace App\Services\Stripe;

use App\Exceptions\InvalidStripeSignatureException;
use App\Models\Company;

interface StripeGateway
{
    public function ensureCustomer(Company $company): string;

    /**
     * @param  array<string, string>  $metadata
     */
    public function createCheckoutSession(
        string $customerId,
        int $amountCents,
        string $successUrl,
        string $cancelUrl,
        array $metadata,
        string $idempotencyKey,
    ): StripeCheckoutSession;

    /**
     * @throws InvalidStripeSignatureException
     */
    public function parseWebhook(string $payload, string $signatureHeader): StripeWebhookEvent;
}
