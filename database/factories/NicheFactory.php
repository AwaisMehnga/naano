<?php

namespace Database\Factories;

use App\Models\Niche;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Niche>
 */
class NicheFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
