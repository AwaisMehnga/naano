<?php

namespace App\Services\Stripe;

use App\Exceptions\InvalidStripeSignatureException;
use App\Models\Company;
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
