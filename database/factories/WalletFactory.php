<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'available_cents' => 0,
            'currency' => 'EUR',
        ];
    }
}
