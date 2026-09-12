<?php

namespace Database\Factories;

use App\Enums\CampaignObjective;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->catchPhrase(),
            'type' => CampaignType::ThoughtLeadership,
            'objective' => CampaignObjective::Pipeline,
            'status' => CampaignStatus::Draft,
            'budget_cents' => 100000,
            'brief' => null,
            'created_by_user_id' => User::factory(),
        ];
    }
}
