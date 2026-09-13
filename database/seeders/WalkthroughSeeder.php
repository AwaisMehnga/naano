<?php

namespace Database\Seeders;

use App\Enums\CompanyMemberRole;
use App\Models\CompanyIcp;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalkthroughSeeder extends Seeder
{
    public const COMPANY_EMAIL = 'company@naano.test';

    public const CREATOR_EMAIL = 'creator@naano.test';

    public const PASSWORD = 'Walkthrough1!';

    /**
     * Seed the two walkthrough accounts and a few extra company workspaces.
     */
    public function run(): void
    {
        $this->northline();
        $this->extraCompanies();
    }

    public static function credentials(): string
    {
        return implode("\n", [
            'Walkthrough logins (password: '.self::PASSWORD.')',
            '  Company  '.self::COMPANY_EMAIL.'  — Northline, Claire Morel',
            '  Creator  '.self::CREATOR_EMAIL.'  — Maya Elbaz',
        ]);
    }

    private function northline(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => self::COMPANY_EMAIL],
            [
                'name' => 'Claire Morel',
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
            ],
        );

        if (! $user->hasRole('company')) {
            $user->assignRole('company');
        }

        $company = $user->company ?? $user->company()->create([
            'onboarded_at' => now(),
        ]);

        $company->fill([
            'name' => 'Northline',
            'website' => 'https://northline.example',
            'billing_email' => self::COMPANY_EMAIL,
            'country' => 'FR',
            'value_proposition' => 'Northline helps B2B SaaS teams book LinkedIn creators their buyers already follow, at a fixed price per post.',
            'icps' => [
                ['title' => 'Demand gen lead', 'description' => 'Owns pipeline this quarter and needs posts that reach VP Sales.'],
                ['title' => 'Product marketing', 'description' => 'Needs a launch narrative operators will actually share.'],
                ['title' => 'Founder', 'description' => 'Wants proof that creator spend maps to leads, not vanity reach.'],
            ],
            'audience_targeting' => [
                'industries' => ['SaaS', 'B2B', 'Growth / GTM'],
                'regions' => ['FR', 'DE', 'GB', 'NL'],
                'titles' => ['Founder', 'Head of Growth', 'VP Sales'],
                'seniority' => ['Founder', 'Director', 'VP'],
                'company_sizes' => ['11-50', '51-200'],
            ],
            'onboarded_at' => now(),
        ]);
        $company->save();

        if ($company->members()->where('user_id', $user->id)->doesntExist()) {
            $company->members()->create([
                'user_id' => $user->id,
                'role' => CompanyMemberRole::Owner,
                'joined_at' => now(),
            ]);
        }

        if ($company->companyIcps()->doesntExist()) {
            foreach ([
                ['title' => 'Demand gen lead', 'description' => 'Owns pipeline this quarter.', 'sort_order' => 0, 'industries' => ['SaaS', 'B2B'], 'regions' => ['FR', 'DE', 'GB']],
                ['title' => 'Product marketing', 'description' => 'Needs a launch narrative.', 'sort_order' => 1, 'industries' => ['SaaS'], 'regions' => ['EU']],
            ] as $icp) {
                CompanyIcp::query()->create([
                    'company_id' => $company->id,
                    ...$icp,
                ]);
            }
        }

        Wallet::query()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'available_cents' => 250000,
                'currency' => 'EUR',
            ],
        );
    }

    private function extraCompanies(): void
    {
        foreach ([
            [
                'name' => 'Hana Okada',
                'email' => 'hana@heliolabs.test',
                'company' => 'Helio Labs',
                'website' => 'https://heliolabs.example',
                'country' => 'DE',
                'value_proposition' => 'Developer tools for teams that ship AI features into existing products.',
            ],
            [
                'name' => 'Owen Blake',
                'email' => 'owen@cobalt.test',
                'company' => 'Cobalt Payroll',
                'website' => 'https://cobaltpayroll.example',
                'country' => 'GB',
                'value_proposition' => 'Payroll for European contractors who get paid in days, not weeks.',
            ],
        ] as $row) {
            if (User::query()->where('email', $row['email'])->exists()) {
                continue;
            }

            $user = User::factory()->company()->onboarded()->create([
                'name' => $row['name'],
                'email' => $row['email'],
            ]);

            $user->company->update([
                'name' => $row['company'],
                'website' => $row['website'],
                'country' => $row['country'],
                'billing_email' => $row['email'],
                'value_proposition' => $row['value_proposition'],
                'onboarded_at' => now(),
            ]);
        }
    }
}
