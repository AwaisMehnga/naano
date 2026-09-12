<?php

namespace Database\Factories;

use App\Models\Collaboration;
use App\Models\TrackingLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrackingLink>
 */
class TrackingLinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collaboration_id' => Collaboration::factory(),
            'destination_url' => 'https://example.com',
            'utm_source' => 'naano',
            'utm_medium' => 'linkedin',
            'utm_campaign' => fake()->slug(),
            'slug' => Str::random(8),
        ];
    }
}
