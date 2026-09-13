<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CreatorMatchScore;
use App\Models\CreatorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreatorMatchScore>
 */
class CreatorMatchScoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'creator_profile_id' => CreatorProfile::factory(),
            'fit_score' => fake()->numberBetween(0, 100),
            'audience_relevance' => fake()->numberBetween(0, 100),
            'reasons' => ['Audience overlap in SaaS'],
            'computed_at' => now(),
        ];
    }
}
