<?php

use App\Ai\Agents\CampaignFitAgent;
use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Enums\CreatorVettingStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\CreatorMatchScore;
use App\Models\CreatorProfile;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

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

function creatorMatchScore(
    Campaign $campaign,
    CreatorProfile $creator,
    int $fitScore = 82,
    int $audienceRelevance = 74,
): CreatorMatchScore {
    return CreatorMatchScore::factory()->create([
        'campaign_id' => $campaign->id,
        'creator_profile_id' => $creator->id,
        'fit_score' => $fitScore,
        'audience_relevance' => $audienceRelevance,
        'reasons' => ['Audience overlap in SaaS'],
        'computed_at' => now(),
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
        ->withHeaders(['X-Profile-Type' => 'company'])
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
