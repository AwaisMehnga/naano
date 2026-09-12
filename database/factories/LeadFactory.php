<?php

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'campaign_id' => Campaign::factory(),
            'occurred_at' => now(),
            'source' => LeadSource::Manual,
        ];
    }
}
