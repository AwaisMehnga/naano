<?php

namespace Database\Factories;

use App\Enums\CreatorVettingStatus;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreatorProfile>
 */
class CreatorProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'linkedin_url' => 'https://www.linkedin.com/in/'.fake()->userName(),
            'headline' => fake()->sentence(6),
            'country' => 'FR',
            'industries' => ['SaaS'],
            'price_cents' => 24000,
        ];
    }

    public function vetted(): static
    {
        return $this->state(fn (): array => [
            'vetting_status' => CreatorVettingStatus::Vetted,
            'onboarded_at' => now(),
        ]);
    }
}
