<?php

namespace Database\Factories;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanySubscription>
 */
class CompanySubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plan' => SubscriptionPlan::SelfServe,
            'status' => SubscriptionStatus::Active,
        ];
    }
}
