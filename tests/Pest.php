<?php

use App\Ai\Agents\CampaignFitAgent;
use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Enums\CompanyMemberRole;
use App\Enums\CreatorVettingStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\CreatorProfile;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function fakeCampaignFit(int $fitScore = 82, int $audienceRelevance = 74): void
{
    CampaignFitAgent::fake([
        [
            'fit_score' => $fitScore,
            'audience_relevance' => $audienceRelevance,
            'reasons' => ['Audience overlap in SaaS'],
        ],
    ]);
}

function marketplaceCreator(array $overrides = []): CreatorProfile
{
    $user = User::factory()->creator()->onboarded()->create();

    $user->creatorProfile->update(array_merge([
        'vetting_status' => CreatorVettingStatus::Vetted,
        'display_name' => 'Ada Lovelace',
        'headline' => 'B2B creator',
        'bio' => 'Writes for SaaS',
        'country' => 'FR',
        'price_cents' => 24000,
    ], $overrides));

    return $user->creatorProfile->fresh();
}

/**
 * @return array{0: User, 1: Company, 2: User}
 */
function companyWithMember(): array
{
    $owner = User::factory()->company()->onboarded()->create();
    $company = $owner->companies()->first();
    $member = User::factory()->company()->onboarded()->create();

    CompanyMember::factory()->create([
        'company_id' => $company->id,
        'user_id' => $member->id,
        'role' => CompanyMemberRole::Member,
        'joined_at' => now(),
    ]);

    return [$owner, $company, $member];
}

/**
 * @return array{0: User, 1: Collaboration, 2: User}
 */
function bookedDeal(?User $owner = null): array
{
    $owner ??= User::factory()->company()->onboarded()->create();
    $company = $owner->company;
    $campaign = Campaign::factory()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();
    $collaboration = Collaboration::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'status' => CollaborationStatus::Selected,
        'source' => CollaborationSource::Invite,
    ]);

    Wallet::query()->updateOrCreate(
        ['company_id' => $company->id],
        ['available_cents' => 50000, 'currency' => 'EUR'],
    );

    test()->actingAs($owner)
        ->postJson(route('api.company.collaborations.book', $collaboration))
        ->assertOk();

    return [$owner, $collaboration->fresh(), $creator->user];
}

function fakePng(string $name = 'photo.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='),
    );
}
