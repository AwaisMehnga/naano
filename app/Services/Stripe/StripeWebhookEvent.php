<?php

namespace App\Services\Stripe;

final class StripeWebhookEvent
{
    /**
     * @param  array<string, mixed>  $object
     */
    public function __construct(
        public string $id,
        public string $type,
        public array $object,
    ) {}

    public function metadata(string $key): ?string
    {
        $meta = $this->object['metadata'] ?? null;

        if (! is_array($meta)) {
            return null;
        }

        $value = $meta[$key] ?? null;

        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        return null;
    }

    public function customerId(): ?string
    {
        $customer = $this->object['customer'] ?? null;

        return is_string($customer) && $customer !== '' ? $customer : null;
    }
}
