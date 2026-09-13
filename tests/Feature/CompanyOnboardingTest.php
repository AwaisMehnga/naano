<?php

use App\Ai\Agents\BrandBriefAgent;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('company onboarding starts on the website step', function () {
    $user = User::factory()->company()->create();

    $this->actingAs($user)
        ->get(route('onboarding.company'))
        ->assertOk()
        ->assertSee('Website');
});

test('company website analysis saves a brief', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com' => Http::response('<html><body>Finance software for operators</body></html>'),
    ]);

    BrandBriefAgent::fake([
        [
            'value_proposition' => 'Example sells finance software to operators who need fewer tools in one place.',
            'icp_1_title' => 'Ops lead',
            'icp_1_description' => 'Runs the daily stack.',
            'icp_2_title' => 'CFO',
            'icp_2_description' => 'Owns spend and reporting.',
            'icp_3_title' => 'Founder',
            'icp_3_description' => 'Wants a tight operating cadence.',
        ],
    ]);

    $user = User::factory()->company()->create();

    $this->actingAs($user)
        ->post(route('onboarding.company.website'), [
            'website' => 'https://example.com',
        ])
        ->assertRedirect(route('onboarding.company'));

    $company = $user->fresh()->company;

    expect($company->website)->toBe('https://example.com')
        ->and($company->value_proposition)->toContain('finance software')
        ->and($company->icps)->toHaveCount(3)
        ->and($company->icps[0]['title'])->toBe('Ops lead');
});

test('company can confirm the brief and open the workspace', function () {
    $user = User::factory()->company()->create();
    $user->company->update(['website' => 'https://example.com']);

    $this->actingAs($user)
        ->post(route('onboarding.company.brief'), [
            'value_proposition' => 'We sell workflow software to mid-market finance teams who need a single source of truth.',
            'icps' => [
                ['title' => 'Controller', 'description' => 'Closes the books each month.'],
                ['title' => 'RevOps', 'description' => 'Connects CRM to billing.'],
                ['title' => 'Founder', 'description' => 'Wants visibility without extra hires.'],
            ],
        ])
        ->assertRedirect(route('company'));

    expect($user->fresh()->company->onboarded_at)->not->toBeNull();
});

test('company cannot confirm a brief before adding a website', function () {
    $user = User::factory()->company()->create();
    $user->company->update(['website' => null]);

    $this->actingAs($user)
        ->post(route('onboarding.company.brief'), [
            'value_proposition' => 'We sell workflow software to mid-market finance teams who need a single source of truth.',
            'icps' => [
                ['title' => 'Controller', 'description' => 'Closes the books each month.'],
                ['title' => 'RevOps', 'description' => 'Connects CRM to billing.'],
                ['title' => 'Founder', 'description' => 'Wants visibility without extra hires.'],
            ],
        ]);

    expect($user->fresh()->company->onboarded_at)->toBeNull();
});
