<?php

namespace App\Services\Stripe;

use App\Exceptions\InvalidStripeSignatureException;
use App\Models\Company;
use App\Models\CreatorProfile;

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

    public function createConnectAccount(CreatorProfile $profile, string $email): string;

    public function connectAccountCountry(string $accountId): ?string;

    public function createAccountLink(string $accountId, string $refreshUrl, string $returnUrl): string;

    public function createLoginLink(string $accountId): string;

    /**
     * @param  array<string, string>  $metadata
     */
    public function createTransfer(
        string $destination,
        int $amountCents,
        array $metadata,
        string $idempotencyKey,
    ): string;

    /**
     * @throws InvalidStripeSignatureException
     */
    public function parseWebhook(string $payload, string $signatureHeader): StripeWebhookEvent;
}
