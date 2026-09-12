<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyIcp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyIcp>
 */
class CompanyIcpFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
