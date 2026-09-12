<?php

namespace Database\Factories;

use App\Models\CreatorAudienceProfile;
use App\Models\CreatorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreatorAudienceProfile>
 */
class CreatorAudienceProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_profile_id' => CreatorProfile::factory(),
            'network' => 'linkedin',
            'followers_count' => fake()->numberBetween(1000, 50000),
            'audience_mix' => [
                'geo' => ['FR' => 40, 'US' => 30, 'GB' => 30],
            ],
            'captured_at' => now(),
        ];
    }
}
