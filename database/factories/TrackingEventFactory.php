<?php

namespace Database\Factories;

use App\Models\TrackingEvent;
use App\Models\TrackingLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrackingEvent>
 */
class TrackingEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracking_link_id' => TrackingLink::factory(),
            'visitor_key' => (string) Str::uuid(),
            'type' => 'qualify',
            'occurred_at' => now(),
        ];
    }
}
