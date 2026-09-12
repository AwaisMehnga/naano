<?php

namespace Database\Factories;

use App\Enums\CompanyMemberRole;
use App\Models\Company;
use App\Models\CompanyInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyInvite>
 */
class CompanyInviteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => CompanyMemberRole::Member,
            'invited_by_user_id' => User::factory(),
        ];
    }
}
