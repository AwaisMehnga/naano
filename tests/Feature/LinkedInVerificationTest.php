<?php

use App\Models\User;
use App\Services\LinkedIn\LinkedInProfileNormalizer;

test('creator can start linkedin verification and receive a code', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->postJson(route('api.creator.linkedin.start'), [
            'linkedin_url' => 'https://www.linkedin.com/in/ada',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data' => ['verify_code', 'linkedin_url']]);

    $profile = $user->fresh()->creatorProfile;

    expect($profile->linkedin_url)->toBe('https://www.linkedin.com/in/ada')
        ->and($profile->linkedin_verify_code)->toHaveLength(8)
        ->and($profile->linkedin_verified_at)->toBeNull();
});

test('linkedin profile endpoint returns presenter aggregates', function () {
    $user = User::factory()->creator()->onboarded()->create();
    $profile = $user->creatorProfile;
    $profile->update([
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
        'linkedin_verified_at' => now(),
        'linkedin_synced_at' => now(),
        'price_cents' => 25000,
        'linkedin_profile' => [
            'public_identifier' => 'ada',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'headline' => 'Writer',
            'summary' => 'About',
            'location' => 'Paris',
            'country_code' => 'FR',
            'follower_count' => 900,
            'connections_count' => 400,
            'picture_url' => null,
            'current_company' => ['name' => 'Analytical Engines', 'url' => null],
            'positions' => [],
            'educations' => [],
            'skills' => ['Writing'],
            'engagers' => null,
            'captured_at' => now()->toIso8601String(),
        ],
        'linkedin_posts' => [
            [
                'id' => '1',
                'linkedin_url' => 'https://www.linkedin.com/posts/1',
                'content' => 'Hello',
                'posted_at' => '2026-09-01T00:00:00Z',
                'likes' => 10,
                'comments' => 2,
                'shares' => 0,
                'reactions' => [['type' => 'LIKE', 'count' => 10]],
            ],
            [
                'id' => '2',
                'linkedin_url' => 'https://www.linkedin.com/posts/2',
                'content' => 'World',
                'posted_at' => '2026-09-08T00:00:00Z',
                'likes' => 30,
                'comments' => 4,
                'shares' => 1,
                'reactions' => [['type' => 'LIKE', 'count' => 30]],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->getJson(route('api.creator.linkedin.profile'))
        ->assertOk()
        ->assertJsonPath('data.verified', true)
        ->assertJsonPath('data.stats.followers', 900)
        ->assertJsonPath('data.stats.posts_count', 2)
        ->assertJsonPath('data.stats.avg_reactions', 20)
        ->assertJsonPath('data.stats.asking_rate_cents', 25000)
        ->assertJsonPath('data.top_posts.0.id', '2')
        ->assertJsonCount(2, 'data.engagement_series');
});

test('refresh is rejected when not verified', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->postJson(route('api.creator.linkedin.refresh'))
        ->assertStatus(422);
});

test('guest cannot access linkedin endpoints', function () {
    $this->postJson(route('api.creator.linkedin.start'), [
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
    ])->assertUnauthorized();

    $this->getJson(route('api.creator.linkedin.profile'))->assertUnauthorized();
});

test('profile normalizer maps supreme_coder apify fixture into stored shape', function () {
    $normalized = app(LinkedInProfileNormalizer::class)->normalize([
        'firstName' => 'Muhammad',
        'lastName' => 'Awais',
        'headline' => 'Learning, Building and Making It Open Source 2CYQLR8C',
        'publicIdentifier' => 'awaismehnga',
        'jobTitle' => 'Senior Full Stack Engineer',
        'summary' => 'Full stack developer.',
        'geoLocationName' => 'Lahore, Punjab, Pakistan',
        'countryCode' => 'PK',
        'followerCount' => 1055,
        'connectionsCount' => 998,
        'companyName' => 'Remote Shifts',
        'companyLinkedinUrl' => 'https://www.linkedin.com/company/remote-shifts/',
        'pictureUrl' => [
            '100x100' => 'https://example.com/100.jpg',
            '800x800' => 'https://example.com/800.jpg',
        ],
        'positions' => [
            [
                'title' => 'Senior Full Stack Engineer',
                'locationName' => 'Sydney, NSW · Remote',
                'timePeriod' => [
                    'startDate' => ['month' => 7, 'year' => 2026],
                    'endDate' => null,
                ],
                'description' => 'Building products.',
                'company' => [
                    'name' => 'Remote Shifts',
                    'url' => 'https://www.linkedin.com/company/108130219/',
                ],
            ],
        ],
        'educations' => [
            [
                'schoolName' => 'COMSATS University Islamabad',
                'degreeName' => 'Bachelor of Science - BS',
                'fieldOfStudy' => 'Computer Science',
                'timePeriod' => [
                    'startDate' => ['month' => 2, 'year' => 2022],
                    'endDate' => ['month' => 1, 'year' => 2026],
                ],
            ],
        ],
        'skills' => [
            ['name' => 'Laravel'],
            ['name' => 'React.js'],
        ],
    ]);

    expect($normalized['first_name'])->toBe('Muhammad')
        ->and($normalized['last_name'])->toBe('Awais')
        ->and($normalized['public_identifier'])->toBe('awaismehnga')
        ->and($normalized['follower_count'])->toBe(1055)
        ->and($normalized['connections_count'])->toBe(998)
        ->and($normalized['country_code'])->toBe('PK')
        ->and($normalized['location'])->toBe('Lahore, Punjab, Pakistan')
        ->and($normalized['picture_url'])->toBe('https://example.com/800.jpg')
        ->and($normalized['current_company']['name'])->toBe('Remote Shifts')
        ->and($normalized['positions'][0]['company'])->toBe('Remote Shifts')
        ->and($normalized['positions'][0]['start'])->toBe('2026-07')
        ->and($normalized['educations'][0]['school'])->toBe('COMSATS University Islamabad')
        ->and($normalized['skills'])->toContain('Laravel')
        ->and($normalized)->toHaveKey('captured_at');
});

test('profile normalizer still maps legacy basic_info fixtures', function () {
    $normalized = app(LinkedInProfileNormalizer::class)->normalize([
        'basic_info' => [
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'headline' => 'Writer',
            'public_identifier' => 'ada',
            'follower_count' => 10,
            'connection_count' => 5,
            'location' => ['full' => 'Paris', 'country_code' => 'fr'],
        ],
        'skills' => ['Writing'],
    ]);

    expect($normalized['first_name'])->toBe('Ada')
        ->and($normalized['follower_count'])->toBe(10)
        ->and($normalized['country_code'])->toBe('FR')
        ->and($normalized['skills'])->toBe(['Writing']);
});
