<?php

namespace App\Services;

use App\Enums\PayoutStatus;
use App\Models\CreatorProfile;
use App\Models\Payout;
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
            'account.updated', 'v2.core.account.updated' => $this->updateConnectAccount($event),
            'transfer.created' => $this->markPayoutInTransit($event),
            'transfer.reversed', 'payout.failed' => $this->failPayout($event),
            'payout.paid' => $this->markPayoutPaid($event),
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

    private function updateConnectAccount(StripeWebhookEvent $event): void
    {
        $accountId = $event->object['id'] ?? data_get($event->object, 'related_object.id');

        if (! is_string($accountId) || $accountId === '') {
            return;
        }

        $profile = CreatorProfile::query()->where('stripe_connect_id', $accountId)->first();

        if (! $profile instanceof CreatorProfile) {
            return;
        }

        $profile->payouts_enabled = $this->connectPayoutsEnabled($event->object);
        $profile->save();
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function connectPayoutsEnabled(array $object): bool
    {
        if (array_key_exists('payouts_enabled', $object)) {
            return (bool) $object['payouts_enabled'];
        }

        $status = data_get($object, 'configuration.recipient.capabilities.stripe_balance.payouts.status')
            ?? data_get($object, 'configuration.recipient.capabilities.stripe_balance.stripe_transfers.status');

        return $status === 'active';
    }

    private function markPayoutInTransit(StripeWebhookEvent $event): void
    {
        $payout = $this->payoutFromEvent($event);

        if (! $payout instanceof Payout || $payout->status !== PayoutStatus::Pending) {
            return;
        }

        $transferId = $event->object['id'] ?? null;

        if (is_string($transferId) && $transferId !== '') {
            $payout->stripe_transfer_id = $transferId;
        }

        $payout->status = PayoutStatus::InTransit;
        $payout->save();
    }

    private function failPayout(StripeWebhookEvent $event): void
    {
        $payout = $this->payoutFromEvent($event);

        if (! $payout instanceof Payout) {
            return;
        }

        $payout->status = PayoutStatus::Failed;
        $payout->save();
    }

    private function markPayoutPaid(StripeWebhookEvent $event): void
    {
        $payout = $this->payoutFromEvent($event);

        if (! $payout instanceof Payout) {
            return;
        }

        $payout->status = PayoutStatus::Paid;
        $payout->paid_at = now();
        $payout->save();
    }

    private function payoutFromEvent(StripeWebhookEvent $event): ?Payout
    {
        $id = $event->metadata('payout_id');

        if ($id !== null && ctype_digit($id)) {
            return Payout::query()->find((int) $id);
        }

        $transferId = $event->object['id'] ?? null;

        if (is_string($transferId) && $transferId !== '') {
            return Payout::query()->where('stripe_transfer_id', $transferId)->first();
        }

        $source = $event->object['source_transfer'] ?? $event->object['balance_transaction'] ?? null;

        if (is_string($source) && $source !== '') {
            return Payout::query()->where('stripe_transfer_id', $source)->first();
        }

        return null;
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
