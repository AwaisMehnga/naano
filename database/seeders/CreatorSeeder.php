<?php

namespace Database\Seeders;

use App\Enums\CreatorVettingStatus;
use App\Enums\OfferLabel;
use App\Models\CreatorAudienceProfile;
use App\Models\CreatorOffer;
use App\Models\Niche;
use App\Models\User;
use Illuminate\Database\Seeder;

class CreatorSeeder extends Seeder
{
    /**
     * Seed vetted marketplace creators for local browsing.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            NicheSeeder::class,
        ]);

        foreach ($this->creators() as $row) {
            if (User::query()->where('email', $row['email'])->exists()) {
                continue;
            }

            $user = User::factory()->creator()->onboarded()->create([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => $row['email'] === WalkthroughSeeder::CREATOR_EMAIL
                    ? WalkthroughSeeder::PASSWORD
                    : 'password',
            ]);

            $profile = $user->creatorProfile;
            $profile->update([
                'display_name' => $row['name'],
                'headline' => $row['headline'],
                'bio' => $row['bio'],
                'linkedin_url' => $row['linkedin_url'],
                'country' => $row['country'],
                'industries' => $row['industries'],
                'price_cents' => $row['price_cents'],
                'vetting_status' => CreatorVettingStatus::Vetted,
                'onboarded_at' => now(),
            ]);

            $nicheIds = Niche::query()
                ->whereIn('slug', $row['niche_slugs'])
                ->pluck('id');

            $profile->niches()->sync($nicheIds->all());

            CreatorAudienceProfile::factory()->create([
                'creator_profile_id' => $profile->id,
                'followers_count' => $row['followers_count'],
                'audience_mix' => $row['audience_mix'],
                'captured_at' => now()->subDays(3),
            ]);

            CreatorOffer::factory()->create([
                'creator_profile_id' => $profile->id,
                'label' => OfferLabel::SinglePost,
                'posts_count' => 1,
                'price_cents' => $row['price_cents'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return list<array{
     *     name: string,
     *     email: string,
     *     headline: string,
     *     bio: string,
     *     linkedin_url: string,
     *     country: string,
     *     industries: list<string>,
     *     niche_slugs: list<string>,
     *     price_cents: int,
     *     followers_count: int,
     *     audience_mix: array<string, array<string, int>>
     * }>
     */
    private function creators(): array
    {
        return [
            [
                'name' => 'Maya Elbaz',
                'email' => WalkthroughSeeder::CREATOR_EMAIL,
                'headline' => 'Growth / GTM · SaaS',
                'bio' => 'Weekly GTM breakdowns for European operators. Founders and demand-gen leads already in the feed.',
                'linkedin_url' => 'https://www.linkedin.com/in/maya-elbaz',
                'country' => 'FR',
                'industries' => ['Growth / GTM', 'SaaS', 'B2B'],
                'niche_slugs' => ['growth-gtm', 'saas', 'b2b'],
                'price_cents' => 28000,
                'followers_count' => 24600,
                'audience_mix' => [
                    'job_title' => ['Founders' => 38, 'Marketing' => 34, 'Sales' => 18, 'Other' => 10],
                    'seniority' => ['Founder' => 42, 'Director' => 31, 'Manager' => 27],
                    'geo' => ['FR' => 35, 'DE' => 25, 'GB' => 25, 'NL' => 15],
                ],
            ],
            [
                'name' => 'Somitra Sinha',
                'email' => 'somitra@example.com',
                'headline' => 'AI · SaaS',
                'bio' => 'Writes for B2B SaaS teams shipping AI into existing products.',
                'linkedin_url' => 'https://www.linkedin.com/in/somitra-sinha',
                'country' => 'IN',
                'industries' => ['AI', 'SaaS'],
                'niche_slugs' => ['ai', 'saas'],
                'price_cents' => 9400,
                'followers_count' => 10400,
                'audience_mix' => [
                    'job_title' => ['Founders' => 42, 'Product' => 28, 'Engineering' => 18, 'Other' => 12],
                    'seniority' => ['Founder' => 40, 'Director' => 32, 'IC' => 28],
                    'geo' => ['IN' => 45, 'US' => 30, 'GB' => 25],
                ],
            ],
            [
                'name' => 'Phil Shorland',
                'email' => 'phil@example.com',
                'headline' => 'B2B · Marketing',
                'bio' => 'Demand gen posts for European SaaS. Founders and marketing leaders.',
                'linkedin_url' => 'https://www.linkedin.com/in/phil-shorland',
                'country' => 'GB',
                'industries' => ['B2B', 'Marketing'],
                'niche_slugs' => ['b2b', 'marketing'],
                'price_cents' => 40800,
                'followers_count' => 19800,
                'audience_mix' => [
                    'job_title' => ['Founders' => 50, 'Marketing' => 37, 'Engineering' => 5, 'Other' => 8],
                    'seniority' => ['Founder' => 61, 'Senior' => 8, 'Manager' => 26, 'Other' => 5],
                    'geo' => ['GB' => 40, 'DE' => 25, 'US' => 20, 'NL' => 15],
                ],
            ],
            [
                'name' => 'Anastasia Shulha',
                'email' => 'anastasia@example.com',
                'headline' => 'Marketing · B2B',
                'bio' => 'GTM narratives for B2B brands that sell to operators.',
                'linkedin_url' => 'https://www.linkedin.com/in/anastasia-shulha',
                'country' => 'DE',
                'industries' => ['Marketing', 'B2B'],
                'niche_slugs' => ['marketing', 'b2b'],
                'price_cents' => 11900,
                'followers_count' => 11300,
                'audience_mix' => [
                    'job_title' => ['Marketing' => 44, 'Founders' => 30, 'Sales' => 16, 'Other' => 10],
                    'seniority' => ['Manager' => 38, 'Founder' => 34, 'Director' => 28],
                    'geo' => ['DE' => 55, 'PL' => 20, 'US' => 15, 'GB' => 10],
                ],
            ],
            [
                'name' => 'Léa Moreau',
                'email' => 'lea@example.com',
                'headline' => 'SaaS · Sales',
                'bio' => 'Pipeline stories for French and EU sales teams.',
                'linkedin_url' => 'https://www.linkedin.com/in/lea-moreau',
                'country' => 'FR',
                'industries' => ['SaaS', 'Sales'],
                'niche_slugs' => ['saas', 'sales'],
                'price_cents' => 24000,
                'followers_count' => 8200,
                'audience_mix' => [
                    'job_title' => ['Sales' => 48, 'Founders' => 27, 'Marketing' => 15, 'Other' => 10],
                    'seniority' => ['Manager' => 40, 'IC' => 35, 'Founder' => 25],
                    'geo' => ['FR' => 60, 'BE' => 20, 'CH' => 20],
                ],
            ],
            [
                'name' => 'Jonah Reed',
                'email' => 'jonah@example.com',
                'headline' => 'Fintech · B2B',
                'bio' => 'Trusted voice for payments, banking, and B2B finance tools.',
                'linkedin_url' => 'https://www.linkedin.com/in/jonah-reed',
                'country' => 'US',
                'industries' => ['Fintech', 'B2B'],
                'niche_slugs' => ['fintech', 'b2b'],
                'price_cents' => 56000,
                'followers_count' => 41200,
                'audience_mix' => [
                    'job_title' => ['Founders' => 33, 'Finance' => 31, 'Product' => 22, 'Other' => 14],
                    'seniority' => ['Director' => 42, 'Founder' => 30, 'VP' => 28],
                    'geo' => ['US' => 70, 'GB' => 15, 'CA' => 15],
                ],
            ],
            [
                'name' => 'Nora Lindqvist',
                'email' => 'nora@example.com',
                'headline' => 'HR · Talent',
                'bio' => 'People-ops posts for HR tech and recruiting teams.',
                'linkedin_url' => 'https://www.linkedin.com/in/nora-lindqvist',
                'country' => 'SE',
                'industries' => ['HR', 'Recruiting / Talent'],
                'niche_slugs' => ['hr', 'recruiting-talent'],
                'price_cents' => 18000,
                'followers_count' => 15600,
                'audience_mix' => [
                    'job_title' => ['HR' => 46, 'Founders' => 24, 'Recruiting' => 20, 'Other' => 10],
                    'seniority' => ['Manager' => 44, 'Director' => 31, 'Founder' => 25],
                    'geo' => ['SE' => 40, 'DE' => 25, 'NL' => 20, 'GB' => 15],
                ],
            ],
            [
                'name' => 'Mateo Ruiz',
                'email' => 'mateo@example.com',
                'headline' => 'Developer Tools · SaaS',
                'bio' => 'Technical storytelling for infra and developer-tool startups.',
                'linkedin_url' => 'https://www.linkedin.com/in/mateo-ruiz',
                'country' => 'ES',
                'industries' => ['Developer Tools', 'SaaS'],
                'niche_slugs' => ['developer-tools', 'saas'],
                'price_cents' => 32000,
                'followers_count' => 22100,
                'audience_mix' => [
                    'job_title' => ['Engineering' => 52, 'Founders' => 22, 'Product' => 18, 'Other' => 8],
                    'seniority' => ['IC' => 48, 'Manager' => 27, 'Founder' => 25],
                    'geo' => ['ES' => 35, 'US' => 30, 'DE' => 20, 'GB' => 15],
                ],
            ],
            [
                'name' => 'Aisha Khan',
                'email' => 'aisha@example.com',
                'headline' => 'Growth / GTM · SaaS',
                'bio' => 'Weekly GTM breakdowns for operators scaling outbound and content.',
                'linkedin_url' => 'https://www.linkedin.com/in/aisha-khan',
                'country' => 'AE',
                'industries' => ['Growth / GTM', 'SaaS'],
                'niche_slugs' => ['growth-gtm', 'saas'],
                'price_cents' => 21000,
                'followers_count' => 9800,
                'audience_mix' => [
                    'job_title' => ['Marketing' => 40, 'Founders' => 35, 'Sales' => 15, 'Other' => 10],
                    'seniority' => ['Founder' => 45, 'Manager' => 33, 'Director' => 22],
                    'geo' => ['AE' => 30, 'GB' => 25, 'IN' => 25, 'US' => 20],
                ],
            ],
        ];
    }
}
