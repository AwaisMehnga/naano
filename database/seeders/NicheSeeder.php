<?php

namespace Database\Seeders;

use App\Models\Niche;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NicheSeeder extends Seeder
{
    /**
     * Seed creator niches from the onboarding industry list.
     */
    public function run(): void
    {
        foreach (config('onboarding.industries') as $index => $name) {
            Niche::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );
        }
    }
}
