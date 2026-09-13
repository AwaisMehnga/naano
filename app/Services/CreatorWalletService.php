<?php

namespace App\Services;

use App\Enums\CollaborationStatus;
use App\Enums\PayoutStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Collaboration;
use App\Models\CreatorProfile;
use App\Models\Payout;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Stripe\StripeGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatorWalletService
{
    public function __construct(
        private CreatorProfileService $profiles,
        private StripeGateway $stripe,
    ) {}

    /**
     * @return array{pending_cents: int, available_cents: int, in_transit_cents: int, paid_cents: int, currency: string, withdrawable: bool}
     */
    public function show(User $user): array
    {
        $profile = $this->profiles->profile($user);

        return $this->balances($profile);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function transactions(User $user): array
    {
        $profile = $this->profiles->profile($user);
        $collabIds = $this->collaborationIds($profile);

        $credits = WalletTransaction::query()
            ->whereIn('collaboration_id', $collabIds)
            ->where('type', WalletTransactionType::Capture)
            ->where('status', WalletTransactionStatus::Posted)
            ->orderByDesc('id')
            ->get()
            ->map(fn (WalletTransaction $transaction): array => [
                'id' => $transaction->id,
                'type' => WalletTransactionType::Capture->value,
                'amount_cents' => $transaction->amount_cents,
                'status' => $transaction->status->value,
                'collaboration_id' => $transaction->collaboration_id,
                'created_at' => $transaction->created_at?->toIso8601String(),
            ]);

        $payouts = $profile->payouts()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Payout $payout): array => [
                'id' => $payout->id,
                'type' => 'payout',
                'amount_cents' => $payout->amount_cents,
                'status' => $payout->status->value,
                'collaboration_id' => $payout->collaboration_id,
                'created_at' => $payout->created_at?->toIso8601String(),
            ]);

        return array_values($credits->concat($payouts)
            ->sortByDesc('created_at')
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function withdraw(User $user, int $amountCents): array
    {
        $profile = $this->profiles->profile($user);
        $minimum = (int) config('wallet.withdraw_min_cents');

        if (! $profile->payouts_enabled || ! is_string($profile->stripe_connect_id) || $profile->stripe_connect_id === '') {
            throw ValidationException::withMessages([
                'payouts_enabled' => 'Connect payouts are not enabled.',
            ]);
        }

        if ($amountCents < $minimum) {
            throw ValidationException::withMessages([
                'amount_cents' => 'The minimum withdrawal is '.$minimum.' cents.',
            ]);
        }

        return DB::transaction(function () use ($profile, $amountCents): array {
            $available = $this->balances($profile)['available_cents'];

            if ($amountCents > $available) {
                throw ValidationException::withMessages([
                    'amount_cents' => 'The withdrawal exceeds available earnings.',
                ]);
            }

            $destination = $profile->stripe_connect_id;

            $payout = Payout::query()->create([
                'creator_profile_id' => $profile->id,
                'collaboration_id' => null,
                'amount_cents' => $amountCents,
                'status' => PayoutStatus::Pending,
            ]);

            $transferId = $this->stripe->createTransfer(
                $destination,
                $amountCents,
                ['payout_id' => (string) $payout->id],
                'payout-'.$payout->id,
            );

            $payout->stripe_transfer_id = $transferId;
            $payout->save();

            return $this->payoutPayload($payout);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function payouts(User $user): array
    {
        $profile = $this->profiles->profile($user);

        return array_values($profile->payouts()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Payout $payout): array => $this->payoutPayload($payout))
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function payout(User $user, Payout $payout): array
    {
        $profile = $this->profiles->profile($user);

        if ($payout->creator_profile_id !== $profile->id) {
            abort(404);
        }

        return $this->payoutPayload($payout);
    }

    public function capturedCents(CreatorProfile $profile): int
    {
        return (int) WalletTransaction::query()
            ->whereIn('collaboration_id', $this->collaborationIds($profile))
            ->where('type', WalletTransactionType::Capture)
            ->where('status', WalletTransactionStatus::Posted)
            ->sum('amount_cents');
    }

    /**
     * @return array{pending_cents: int, available_cents: int, in_transit_cents: int, paid_cents: int, currency: string, withdrawable: bool}
     */
    private function balances(CreatorProfile $profile): array
    {
        $capturedIds = WalletTransaction::query()
            ->whereIn('collaboration_id', $this->collaborationIds($profile))
            ->where('type', WalletTransactionType::Capture)
            ->where('status', WalletTransactionStatus::Posted)
            ->pluck('collaboration_id');

        $pending = (int) Collaboration::query()
            ->where('creator_profile_id', $profile->id)
            ->where('status', CollaborationStatus::Booked)
            ->whereNotIn('id', $capturedIds)
            ->sum('booked_price_cents');

        $captured = $this->capturedCents($profile);
        $inTransit = (int) $profile->payouts()->where('status', PayoutStatus::InTransit)->sum('amount_cents');
        $paid = (int) $profile->payouts()->where('status', PayoutStatus::Paid)->sum('amount_cents');
        $reserved = (int) $profile->payouts()
            ->whereIn('status', [PayoutStatus::Pending, PayoutStatus::InTransit, PayoutStatus::Paid])
            ->sum('amount_cents');
        $available = max(0, $captured - $reserved);
        $minimum = (int) config('wallet.withdraw_min_cents');

        return [
            'pending_cents' => $pending,
            'available_cents' => $available,
            'in_transit_cents' => $inTransit,
            'paid_cents' => $paid,
            'currency' => 'EUR',
            'withdrawable' => $available >= $minimum && $profile->payouts_enabled,
        ];
    }

    /**
     * @return list<int>
     */
    private function collaborationIds(CreatorProfile $profile): array
    {
        return array_values(Collaboration::query()
            ->where('creator_profile_id', $profile->id)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function payoutPayload(Payout $payout): array
    {
        return [
            'id' => $payout->id,
            'amount_cents' => $payout->amount_cents,
            'status' => $payout->status->value,
            'stripe_transfer_id' => $payout->stripe_transfer_id,
            'paid_at' => $payout->paid_at?->toIso8601String(),
            'created_at' => $payout->created_at?->toIso8601String(),
        ];
    }
}
