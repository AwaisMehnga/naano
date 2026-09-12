<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
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
            'name' => fake()->company(),
            'website' => 'https://example.com',
            'value_proposition' => fake()->paragraph(),
            'icps' => [
                ['title' => 'Buyer', 'description' => fake()->sentence()],
                ['title' => 'Operator', 'description' => fake()->sentence()],
                ['title' => 'Founder', 'description' => fake()->sentence()],
            ],
        ];
    }
}
