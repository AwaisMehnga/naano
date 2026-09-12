<?php

namespace Database\Seeders;

use App\Enums\CompanyMemberRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            NicheSeeder::class,
            CreatorSeeder::class,
        ]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->assignRole('company');
        $company = $user->company()->create(['onboarded_at' => now()]);
        $company->members()->create([
            'user_id' => $user->id,
            'role' => CompanyMemberRole::Owner,
            'joined_at' => now(),
        ]);

        $this->call(CampaignSeeder::class);
    }
}
