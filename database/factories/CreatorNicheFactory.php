<?php

namespace Database\Factories;

use App\Models\CreatorNiche;
use App\Models\CreatorProfile;
use App\Models\Niche;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreatorNiche>
 */
class CreatorNicheFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_profile_id' => CreatorProfile::factory(),
            'niche_id' => Niche::factory(),
        ];
    }
}
