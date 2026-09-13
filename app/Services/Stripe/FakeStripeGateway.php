<?php

namespace App\Services\Stripe;

use App\Exceptions\InvalidStripeSignatureException;
use App\Models\Company;
use App\Models\CreatorProfile;
use JsonException;

class FakeStripeGateway implements StripeGateway
{
    public int $checkouts = 0;

    public int $transfers = 0;

    /**
     * @var list<StripeCheckoutSession>
     */
    public array $sessions = [];

    /**
     * @var list<string>
     */
    public array $transferIds = [];

    public function ensureCustomer(Company $company): string
    {
        if (is_string($company->stripe_customer_id) && $company->stripe_customer_id !== '') {
            return $company->stripe_customer_id;
        }

        return 'cus_fake_'.$company->id;
    }

    public function createCheckoutSession(
        string $customerId,
        int $amountCents,
        string $successUrl,
        string $cancelUrl,
        array $metadata,
        string $idempotencyKey,
    ): StripeCheckoutSession {
        $this->checkouts++;
        $id = 'cs_test_'.$this->checkouts.'_'.$idempotencyKey;
        $session = new StripeCheckoutSession(
            $id,
            'https://checkout.stripe.test/pay/'.$id,
            $customerId,
        );
        $this->sessions[] = $session;

        return $session;
    }

    public function createConnectAccount(CreatorProfile $profile, string $email): string
    {
        return 'acct_fake_'.$profile->id;
    }

    public function createAccountLink(string $accountId, string $refreshUrl, string $returnUrl): string
    {
        return 'https://connect.stripe.test/setup/'.$accountId;
    }

    public function createLoginLink(string $accountId): string
    {
        return 'https://connect.stripe.test/login/'.$accountId;
    }

    /**
     * @param  array<string, string>  $metadata
     */
    public function createTransfer(
        string $destination,
        int $amountCents,
        array $metadata,
        string $idempotencyKey,
    ): string {
        $this->transfers++;
        $id = 'tr_fake_'.$this->transfers.'_'.$idempotencyKey;
        $this->transferIds[] = $id;

        return $id;
    }

    public function parseWebhook(string $payload, string $signatureHeader): StripeWebhookEvent
    {
        if ($signatureHeader === '' || $signatureHeader === 'invalid') {
            throw new InvalidStripeSignatureException;
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidStripeSignatureException;
        }

        $id = $decoded['id'] ?? null;
        $type = $decoded['type'] ?? null;
        $object = $decoded['data']['object'] ?? [];

        if (! is_string($id) || $id === '' || ! is_string($type) || $type === '' || ! is_array($object)) {
            throw new InvalidStripeSignatureException;
        }

        return new StripeWebhookEvent($id, $type, $object);
    }
}
