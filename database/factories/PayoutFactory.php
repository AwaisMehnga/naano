<?php

namespace Database\Factories;

use App\Enums\PayoutStatus;
use App\Models\CreatorProfile;
use App\Models\Payout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_profile_id' => CreatorProfile::factory(),
            'collaboration_id' => null,
            'amount_cents' => 24000,
            'status' => PayoutStatus::Pending,
        ];
    }
}
