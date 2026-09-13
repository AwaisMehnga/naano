<?php

namespace App\Services\Stripe;

use App\Exceptions\InvalidStripeSignatureException;
use App\Models\Company;
use App\Models\CreatorProfile;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeSdkGateway implements StripeGateway
{
    public function ensureCustomer(Company $company): string
    {
        if (is_string($company->stripe_customer_id) && $company->stripe_customer_id !== '') {
            return $company->stripe_customer_id;
        }

        $customer = $this->client()->customers->create([
            'name' => $company->name,
            'email' => $company->billing_email,
            'metadata' => [
                'company_id' => (string) $company->id,
            ],
        ]);

        return $customer->id;
    }

    public function createCheckoutSession(
        string $customerId,
        int $amountCents,
        string $successUrl,
        string $cancelUrl,
        array $metadata,
        string $idempotencyKey,
    ): StripeCheckoutSession {
        $session = $this->client()->checkout->sessions->create([
            'customer' => $customerId,
            'mode' => 'payment',
            'currency' => config('services.stripe.currency'),
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            'payment_intent_data' => [
                'metadata' => $metadata,
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => config('services.stripe.currency'),
                    'unit_amount' => $amountCents,
                    'product_data' => [
                        'name' => 'Campaign wallet top-up',
                    ],
                ],
            ]],
        ], [
            'idempotency_key' => $idempotencyKey,
        ]);

        $url = $session->url;

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Stripe did not return a Checkout URL.');
        }

        return new StripeCheckoutSession($session->id, $url, $customerId);
    }

    public function createConnectAccount(CreatorProfile $profile, string $email): string
    {
        $account = $this->client()->accounts->create([
            'type' => 'express',
            'country' => $profile->country ?: 'FR',
            'email' => $email,
            'capabilities' => [
                'transfers' => ['requested' => true],
            ],
            'metadata' => [
                'creator_profile_id' => (string) $profile->id,
            ],
        ], [
            'idempotency_key' => 'connect-acct-'.$profile->id,
        ]);

        return $account->id;
    }

    public function createAccountLink(string $accountId, string $refreshUrl, string $returnUrl): string
    {
        $link = $this->client()->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ], [
            'idempotency_key' => 'connect-link-'.$accountId,
        ]);

        $url = $link->url;

        if ($url === '') {
            throw new RuntimeException('Stripe did not return an Account Link URL.');
        }

        return $url;
    }

    public function createLoginLink(string $accountId): string
    {
        $link = $this->client()->accounts->createLoginLink($accountId, [], [
            'idempotency_key' => 'connect-login-'.$accountId,
        ]);

        $url = $link->url;

        if ($url === '') {
            throw new RuntimeException('Stripe did not return a login link.');
        }

        return $url;
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
        $transfer = $this->client()->transfers->create([
            'amount' => $amountCents,
            'currency' => config('services.stripe.currency'),
            'destination' => $destination,
            'metadata' => $metadata,
        ], [
            'idempotency_key' => $idempotencyKey,
        ]);

        return $transfer->id;
    }

    public function parseWebhook(string $payload, string $signatureHeader): StripeWebhookEvent
    {
        $secret = config('services.stripe.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            throw new InvalidStripeSignatureException;
        }

        try {
            $event = Webhook::constructEvent($payload, $signatureHeader, $secret);
        } catch (SignatureVerificationException|UnexpectedValueException) {
            throw new InvalidStripeSignatureException;
        }

        /** @var array<string, mixed> $object */
        $object = $event->data->object->toArray();

        return new StripeWebhookEvent($event->id, $event->type, $object);
    }

    private function client(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('Stripe is not configured.');
        }

        return new StripeClient($secret);
    }
}
