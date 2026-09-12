<?php

namespace Database\Factories;

use App\Models\StripeEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripeEvent>
 */
class StripeEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stripe_event_id' => 'evt_'.fake()->unique()->uuid(),
            'type' => 'checkout.session.completed',
            'payload' => [],
            'processed_at' => null,
        ];
    }
}
