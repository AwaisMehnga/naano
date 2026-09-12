<?php

namespace Database\Factories;

use App\Enums\CollaborationEventType;
use App\Models\Collaboration;
use App\Models\CollaborationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollaborationEvent>
 */
class CollaborationEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collaboration_id' => Collaboration::factory(),
            'type' => CollaborationEventType::Note,
            'body' => fake()->sentence(),
            'occurred_at' => now(),
        ];
    }
}
