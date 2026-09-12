<?php

namespace App\Services;

use App\Models\StripeEvent;
use App\Models\WalletTransaction;
use App\Services\Stripe\StripeWebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;

class StripeWebhookService
{
    public function __construct(private CompanyWalletService $wallets) {}

    public function handle(StripeWebhookEvent $event): void
    {
        try {
            $row = StripeEvent::query()->firstOrCreate(
                ['stripe_event_id' => $event->id],
                [
                    'type' => $event->type,
                    'payload' => $event->object,
                ],
            );
        } catch (UniqueConstraintViolationException) {
            return;
        }

        if ($row->processed_at !== null) {
            return;
        }

        match ($event->type) {
            'checkout.session.completed' => $this->completeCheckout($event),
            'payment_intent.payment_failed' => $this->failPayment($event),
            default => null,
        };

        $row->processed_at = now();
        $row->save();
    }

    private function completeCheckout(StripeWebhookEvent $event): void
    {
        $transaction = $this->transactionFromEvent($event);

        if ($transaction === null) {
            return;
        }

        $this->wallets->postTopup($transaction, $event->customerId());
    }

    private function failPayment(StripeWebhookEvent $event): void
    {
        $transaction = $this->transactionFromEvent($event);

        if ($transaction === null) {
            return;
        }

        $this->wallets->failTopup($transaction);
    }

    private function transactionFromEvent(StripeWebhookEvent $event): ?WalletTransaction
    {
        $id = $event->metadata('wallet_transaction_id');

        if ($id === null || ! ctype_digit($id)) {
            return null;
        }

        return WalletTransaction::query()->find((int) $id);
    }
}
