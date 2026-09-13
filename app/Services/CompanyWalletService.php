<?php

namespace App\Services;

use App\Enums\CollaborationStatus;
use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Collaboration;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Stripe\StripeGateway;
use App\Support\CompanyAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyWalletService
{
    public function __construct(private StripeGateway $stripe) {}

    public function forCompany(Company $company): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'available_cents' => 0,
                'currency' => 'EUR',
            ],
        );
    }

    /**
     * @return array{available_cents: int, held_cents: int, currency: string, campaign_holds: list<array{campaign_id: int, held_cents: int}>}
     */
    public function show(Company $company): array
    {
        $wallet = $this->forCompany($company);
        $heldByCampaign = $this->heldByCampaign($wallet);

        return [
            'available_cents' => $wallet->available_cents,
            'held_cents' => array_sum($heldByCampaign),
            'currency' => $wallet->currency,
            'campaign_holds' => collect($heldByCampaign)
                ->map(fn (int $cents, int|string $campaignId): array => [
                    'campaign_id' => (int) $campaignId,
                    'held_cents' => $cents,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function transactions(Company $company, array $filters): array
    {
        $wallet = $this->forCompany($company);

        $query = $wallet->transactions()->orderByDesc('id');

        if (isset($filters['campaign_id']) && $filters['campaign_id'] !== null && $filters['campaign_id'] !== '') {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (isset($filters['type']) && $filters['type'] !== null && $filters['type'] !== '') {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query->limit(100)
            ->get()
            ->map(fn (WalletTransaction $transaction): array => $this->transactionPayload($transaction))
            ->all();
    }

    /**
     * @return array{checkout_url: string, stripe_session_id: string, wallet_transaction_id: int}
     */
    public function startTopup(Company $company, User $actor, int $amountCents): array
    {
        CompanyAccess::ensureCanManageMoney($actor, $company);

        $minimum = (int) config('wallet.topup_min_cents');

        if ($amountCents < $minimum) {
            throw ValidationException::withMessages([
                'amount_cents' => 'The minimum top-up is '.$minimum.' cents.',
            ]);
        }

        $wallet = $this->forCompany($company);
        $customerId = $this->stripe->ensureCustomer($company);

        if ($company->stripe_customer_id !== $customerId) {
            $company->stripe_customer_id = $customerId;
            $company->save();
        }

        $transaction = $wallet->transactions()->create([
            'type' => WalletTransactionType::Topup,
            'direction' => WalletTransactionDirection::Credit,
            'amount_cents' => $amountCents,
            'status' => WalletTransactionStatus::Pending,
        ]);

        $session = $this->stripe->createCheckoutSession(
            $customerId,
            $amountCents,
            url('/company/wallet?topup=success'),
            url('/company/wallet?topup=cancel'),
            [
                'company_id' => (string) $company->id,
                'wallet_transaction_id' => (string) $transaction->id,
            ],
            'wallet-topup-'.$transaction->id,
        );

        $transaction->stripe_id = $session->id;
        $transaction->save();

        return [
            'checkout_url' => $session->url,
            'stripe_session_id' => $session->id,
            'wallet_transaction_id' => $transaction->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showTopup(Company $company, WalletTransaction $transaction): array
    {
        $this->ensureOwned($company, $transaction);

        if ($transaction->type !== WalletTransactionType::Topup) {
            abort(404);
        }

        return $this->transactionPayload($transaction);
    }

    public function postTopup(WalletTransaction $transaction, ?string $customerId = null): void
    {
        if ($transaction->type !== WalletTransactionType::Topup) {
            return;
        }

        DB::transaction(function () use ($transaction, $customerId): void {
            $wallet = Wallet::query()
                ->where('id', $transaction->wallet_id)
                ->lockForUpdate()
                ->first();

            if (! $wallet instanceof Wallet) {
                return;
            }

            $locked = WalletTransaction::query()
                ->where('id', $transaction->id)
                ->lockForUpdate()
                ->first();

            if (! $locked instanceof WalletTransaction || $locked->status !== WalletTransactionStatus::Pending) {
                return;
            }

            $locked->status = WalletTransactionStatus::Posted;
            $locked->save();

            $wallet->available_cents += $locked->amount_cents;
            $wallet->save();

            if (is_string($customerId) && $customerId !== '') {
                $company = $wallet->company;

                if ($company->stripe_customer_id !== $customerId) {
                    $company->stripe_customer_id = $customerId;
                    $company->save();
                }
            }
        });
    }

    public function failTopup(WalletTransaction $transaction): void
    {
        if ($transaction->type !== WalletTransactionType::Topup) {
            return;
        }

        if ($transaction->status !== WalletTransactionStatus::Pending) {
            return;
        }

        $transaction->status = WalletTransactionStatus::Failed;
        $transaction->save();
    }

    public function hold(Company $company, Collaboration $collaboration, int $amountCents): void
    {
        DB::transaction(function () use ($company, $collaboration, $amountCents): void {
            $wallet = Wallet::query()
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet instanceof Wallet) {
                $wallet = $this->forCompany($company);
                $wallet = Wallet::query()
                    ->where('id', $wallet->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            if ($wallet->available_cents < $amountCents) {
                throw ValidationException::withMessages([
                    'available_cents' => 'The wallet does not have enough funds to book this creator.',
                ]);
            }

            $wallet->available_cents -= $amountCents;
            $wallet->save();

            $wallet->transactions()->create([
                'campaign_id' => $collaboration->campaign_id,
                'collaboration_id' => $collaboration->id,
                'type' => WalletTransactionType::Hold,
                'direction' => WalletTransactionDirection::Debit,
                'amount_cents' => $amountCents,
                'status' => WalletTransactionStatus::Posted,
            ]);
        });
    }

    public function releaseHold(Company $company, Collaboration $collaboration): void
    {
        DB::transaction(function () use ($company, $collaboration): void {
            $wallet = Wallet::query()
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet instanceof Wallet) {
                return;
            }

            $hasCapture = $wallet->transactions()
                ->where('collaboration_id', $collaboration->id)
                ->where('type', WalletTransactionType::Capture)
                ->where('status', WalletTransactionStatus::Posted)
                ->exists();

            if ($hasCapture) {
                throw ValidationException::withMessages([
                    'status' => 'This booking has already been captured.',
                ]);
            }

            $alreadyReleased = $wallet->transactions()
                ->where('collaboration_id', $collaboration->id)
                ->where('type', WalletTransactionType::Release)
                ->where('status', WalletTransactionStatus::Posted)
                ->exists();

            if ($alreadyReleased) {
                return;
            }

            $hold = $wallet->transactions()
                ->where('collaboration_id', $collaboration->id)
                ->where('type', WalletTransactionType::Hold)
                ->where('status', WalletTransactionStatus::Posted)
                ->orderByDesc('id')
                ->first();

            if (! $hold instanceof WalletTransaction) {
                return;
            }

            $wallet->available_cents += $hold->amount_cents;
            $wallet->save();

            $wallet->transactions()->create([
                'campaign_id' => $collaboration->campaign_id,
                'collaboration_id' => $collaboration->id,
                'type' => WalletTransactionType::Release,
                'direction' => WalletTransactionDirection::Credit,
                'amount_cents' => $hold->amount_cents,
                'status' => WalletTransactionStatus::Posted,
            ]);
        });
    }

    public function captureHold(Company $company, Collaboration $collaboration): void
    {
        DB::transaction(function () use ($company, $collaboration): void {
            $wallet = Wallet::query()
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet instanceof Wallet) {
                throw ValidationException::withMessages([
                    'status' => 'This booking has no hold to capture.',
                ]);
            }

            $hasCapture = $wallet->transactions()
                ->where('collaboration_id', $collaboration->id)
                ->where('type', WalletTransactionType::Capture)
                ->where('status', WalletTransactionStatus::Posted)
                ->exists();

            if ($hasCapture) {
                return;
            }

            $alreadyReleased = $wallet->transactions()
                ->where('collaboration_id', $collaboration->id)
                ->where('type', WalletTransactionType::Release)
                ->where('status', WalletTransactionStatus::Posted)
                ->exists();

            if ($alreadyReleased) {
                throw ValidationException::withMessages([
                    'status' => 'This booking has already been released.',
                ]);
            }

            $hold = $wallet->transactions()
                ->where('collaboration_id', $collaboration->id)
                ->where('type', WalletTransactionType::Hold)
                ->where('status', WalletTransactionStatus::Posted)
                ->orderByDesc('id')
                ->first();

            if (! $hold instanceof WalletTransaction) {
                throw ValidationException::withMessages([
                    'status' => 'This booking has no hold to capture.',
                ]);
            }

            $wallet->transactions()->create([
                'campaign_id' => $collaboration->campaign_id,
                'collaboration_id' => $collaboration->id,
                'type' => WalletTransactionType::Capture,
                'direction' => WalletTransactionDirection::Debit,
                'amount_cents' => $hold->amount_cents,
                'status' => WalletTransactionStatus::Posted,
            ]);

            $collaboration->status = CollaborationStatus::Completed;
            $collaboration->save();
        });
    }

    /**
     * @return array<int, int>
     */
    private function heldByCampaign(Wallet $wallet): array
    {
        $posted = $wallet->transactions()
            ->where('status', WalletTransactionStatus::Posted)
            ->whereIn('type', [
                WalletTransactionType::Hold,
                WalletTransactionType::Capture,
                WalletTransactionType::Release,
            ])
            ->whereNotNull('campaign_id')
            ->get();

        $held = [];

        foreach ($posted as $transaction) {
            $campaignId = (int) $transaction->campaign_id;

            if (! isset($held[$campaignId])) {
                $held[$campaignId] = 0;
            }

            if ($transaction->type === WalletTransactionType::Hold) {
                $held[$campaignId] += $transaction->amount_cents;
            } else {
                $held[$campaignId] -= $transaction->amount_cents;
            }
        }

        return array_filter($held, fn (int $cents): bool => $cents > 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionPayload(WalletTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'direction' => $transaction->direction->value,
            'amount_cents' => $transaction->amount_cents,
            'status' => $transaction->status->value,
            'campaign_id' => $transaction->campaign_id,
            'collaboration_id' => $transaction->collaboration_id,
            'stripe_id' => $transaction->stripe_id,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }

    private function ensureOwned(Company $company, WalletTransaction $transaction): void
    {
        $wallet = $transaction->wallet()->first();

        if ($wallet === null || $wallet->company_id !== $company->id) {
            abort(404);
        }
    }
}
