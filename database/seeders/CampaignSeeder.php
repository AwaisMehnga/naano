<?php

namespace Database\Seeders;

use App\Enums\CampaignObjective;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Seed a few named campaigns so marketplace booking can pick one.
     */
    public function run(): void
    {
        $names = [
            'Q4 Pipeline',
            'Thought leadership EU',
            'Product launch',
            'Hiring brand',
        ];

        foreach (Company::query()->orderBy('id')->get() as $company) {
            foreach ($names as $name) {
                Campaign::query()->firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $name,
                    ],
                    [
                        'type' => CampaignType::ThoughtLeadership,
                        'objective' => CampaignObjective::Pipeline,
                        'status' => CampaignStatus::Active,
                        'budget_cents' => 100000,
                        'created_by_user_id' => $company->user_id,
                    ],
                );
            }
        }
    }
}
