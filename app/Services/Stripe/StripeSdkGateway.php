<?php

namespace App\Services\Stripe;

use App\Exceptions\InvalidStripeSignatureException;
use App\Models\Company;
use App\Models\CreatorProfile;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
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

        $customer = $this->request(fn (): object => $this->client()->customers->create([
            'name' => $company->name,
            'email' => $company->billing_email,
            'metadata' => [
                'company_id' => (string) $company->id,
            ],
        ]));

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
        $session = $this->request(fn (): object => $this->client()->checkout->sessions->create([
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
        ]));

        $url = $session->url;

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Stripe did not return a Checkout URL.');
        }

        return new StripeCheckoutSession($session->id, $url, $customerId);
    }

    public function createConnectAccount(CreatorProfile $profile, string $email): string
    {
        try {
            return $this->createExpressAccount($profile, $email);
        } catch (ApiErrorException $exception) {
            if (! $this->shouldUseAccountsV2($exception)) {
                throw $this->userFacing($exception);
            }

            try {
                return $this->createV2RecipientAccount($profile, $email);
            } catch (ApiErrorException $v2) {
                throw $this->userFacing($v2);
            }
        }
    }

    public function createAccountLink(string $accountId, string $refreshUrl, string $returnUrl): string
    {
        try {
            return $this->createV1AccountLink($accountId, $refreshUrl, $returnUrl);
        } catch (ApiErrorException $exception) {
            try {
                return $this->createV2AccountLink($accountId, $refreshUrl, $returnUrl);
            } catch (ApiErrorException $fallback) {
                throw $this->userFacing($fallback);
            }
        }
    }

    public function createLoginLink(string $accountId): string
    {
        $link = $this->request(fn (): object => $this->client()->accounts->createLoginLink($accountId, [], [
            'idempotency_key' => 'connect-login-'.$accountId,
        ]));

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
        $transfer = $this->request(fn (): object => $this->client()->transfers->create([
            'amount' => $amountCents,
            'currency' => config('services.stripe.currency'),
            'destination' => $destination,
            'metadata' => $metadata,
        ], [
            'idempotency_key' => $idempotencyKey,
        ]));

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

    private function createV2RecipientAccount(CreatorProfile $profile, string $email): string
    {
        $account = $this->request(fn (): object => $this->client()->v2->core->accounts->create([
            'contact_email' => $email,
            'display_name' => $profile->display_name ?: $email,
            'dashboard' => 'express',
            'identity' => [
                'country' => $this->payoutCountry($profile),
                'entity_type' => 'individual',
            ],
            'defaults' => [
                'responsibilities' => [
                    'fees_collector' => 'application',
                    'losses_collector' => 'application',
                ],
            ],
            'configuration' => [
                'merchant' => [
                    'capabilities' => [
                        'card_payments' => [
                            'requested' => true,
                        ],
                    ],
                ],
                'recipient' => [
                    'capabilities' => [
                        'stripe_balance' => [
                            'stripe_transfers' => [
                                'requested' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'metadata' => [
                'creator_profile_id' => (string) $profile->id,
            ],
        ], [
            'idempotency_key' => 'connect-acct-v2-dash-1-'.$profile->id.'-'.$this->payoutCountry($profile),
        ]));

        return $account->id;
    }

    private function createExpressAccount(CreatorProfile $profile, string $email): string
    {
        $account = $this->request(fn (): object => $this->client()->accounts->create([
            'type' => 'express',
            'country' => $this->payoutCountry($profile),
            'email' => $email,
            'capabilities' => [
                'transfers' => [
                    'requested' => true,
                ],
            ],
            'business_profile' => [
                'product_description' => 'LinkedIn creator collaborations',
            ],
            'tos_acceptance' => [
                'service_agreement' => 'recipient',
            ],
            'metadata' => [
                'creator_profile_id' => (string) $profile->id,
            ],
        ], [
            'idempotency_key' => 'connect-acct-v1-recip-1-'.$profile->id.'-'.$this->payoutCountry($profile),
        ]));

        return $account->id;
    }

    private function createV2AccountLink(string $accountId, string $refreshUrl, string $returnUrl): string
    {
        $link = $this->request(fn (): object => $this->client()->v2->core->accountLinks->create([
            'account' => $accountId,
            'use_case' => [
                'type' => 'account_onboarding',
                'account_onboarding' => [
                    'configurations' => ['recipient'],
                    'refresh_url' => $refreshUrl,
                    'return_url' => $returnUrl,
                ],
            ],
        ]));

        return $this->accountLinkUrl($link->url);
    }

    private function createV1AccountLink(string $accountId, string $refreshUrl, string $returnUrl): string
    {
        $link = $this->request(fn (): object => $this->client()->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]));

        return $this->accountLinkUrl($link->url);
    }

    private function accountLinkUrl(mixed $url): string
    {
        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Stripe did not return an Account Link URL.');
        }

        return $url;
    }

    public function connectAccountCountry(string $accountId): ?string
    {
        try {
            $account = $this->request(fn (): object => $this->client()->accounts->retrieve($accountId));
            $country = $account->country ?? null;

            if (is_string($country) && $country !== '') {
                return strtoupper($country);
            }
        } catch (ApiErrorException) {
            // Try Accounts v2 next.
        }

        try {
            $account = $this->request(fn (): object => $this->client()->v2->core->accounts->retrieve($accountId));
            $country = data_get($account, 'identity.country');

            if (is_string($country) && $country !== '') {
                return strtoupper($country);
            }
        } catch (ApiErrorException) {
            return null;
        }

        return null;
    }

    private function payoutCountry(CreatorProfile $profile): string
    {
        $country = $profile->country;

        if (! is_string($country) || $country === '' || $country === 'OTHER') {
            throw ValidationException::withMessages([
                'country' => 'Add your country on your profile before setting up payouts.',
            ]);
        }

        return strtoupper($country);
    }

    private function shouldUseAccountsV2(ApiErrorException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'Accounts v2')
            || str_contains($message, 'v2/core/accounts');
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function request(callable $callback): mixed
    {
        return StripeNotice::ignore($callback);
    }

    private function userFacing(ApiErrorException $exception): ValidationException
    {
        return ValidationException::withMessages([
            'stripe' => [StripeUserMessage::from($exception->getMessage())],
        ]);
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
