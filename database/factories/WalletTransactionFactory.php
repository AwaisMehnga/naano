<?php

namespace Database\Factories;

use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'type' => WalletTransactionType::Topup,
            'direction' => WalletTransactionDirection::Credit,
            'amount_cents' => 10000,
            'status' => WalletTransactionStatus::Posted,
        ];
    }
}
