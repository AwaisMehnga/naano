<?php

use App\Models\CompanyIcp;
use App\Models\User;

test('empty icps are seeded from onboarding jsonb', function () {
    $user = User::factory()->company()->onboarded()->create();
    $company = $user->company;
    $company->update([
        'icps' => [
            ['title' => 'Ops lead', 'description' => 'Runs operations and tools.'],
            ['title' => 'Founder', 'description' => 'Buys for the whole team.'],
            ['title' => 'RevOps', 'description' => 'Owns the pipeline.'],
        ],
    ]);

    $this->actingAs($user)
        ->getJson(route('api.company.icps.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.title', 'Ops lead');

    expect(CompanyIcp::query()->where('company_id', $company->id)->count())->toBe(3);
});

test('owners can update audience targeting and icps', function () {
    $user = User::factory()->company()->onboarded()->create();

    $this->actingAs($user)
        ->patchJson(route('api.company.audience.update'), [
            'industries' => ['B2B', 'SaaS'],
            'regions' => ['Europe'],
            'titles' => ['VP Operations'],
            'seniority' => ['VP', 'Director'],
            'company_sizes' => ['51–200'],
        ])
        ->assertOk()
        ->assertJsonPath('data.targeting.industries', ['B2B', 'SaaS'])
        ->assertJsonPath('data.targeting.regions', ['Europe']);

    $create = $this->actingAs($user)
        ->postJson(route('api.company.icps.store'), [
            'title' => 'Freelance Full-Stack Developer',
            'description' => 'Independent developers seeking high-value clients.',
            'tags' => ['portfolio', 'client acquisition'],
            'industries' => ['Developer Tools'],
            'regions' => ['Europe'],
        ])
        ->assertOk();

    $icpId = $create->json('data.id');

    $this->actingAs($user)
        ->patchJson(route('api.company.icps.update', $icpId), [
            'title' => 'Freelance developer',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Freelance developer');

    $this->actingAs($user)
        ->deleteJson(route('api.company.icps.destroy', $icpId))
        ->assertOk();

    expect(CompanyIcp::query()->where('id', $icpId)->exists())->toBeFalse();
});

test('invalid audience values are rejected', function () {
    $user = User::factory()->company()->onboarded()->create();

    $this->actingAs($user)
        ->patchJson(route('api.company.audience.update'), [
            'regions' => ['Atlantis'],
        ])
        ->assertUnprocessable();
});
