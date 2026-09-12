<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\Collaboration;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collaboration_id' => Collaboration::factory(),
            'status' => ContractStatus::Generated,
            'generated_at' => now(),
        ];
    }
}
