<?php

use App\Ai\Agents\BrandBriefAgent;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('company onboarding starts on the website step', function () {
    $user = User::factory()->company()->create();
    $user->company->update(['website' => null]);

    $this->actingAs($user)
        ->get(route('onboarding.company'))
        ->assertOk()
        ->assertSee('Analyze website');
});

test('company brief heading is not double escaped', function () {
    $user = User::factory()->company()->create();

    $this->actingAs($user)
        ->get(route('onboarding.company'))
        ->assertOk()
        ->assertSee('Value prop & ICP')
        ->assertSee('Back to website');
});

test('company can return to the website step after analysis', function () {
    $user = User::factory()->company()->create();

    $this->actingAs($user)
        ->get(route('onboarding.company', ['step' => 'website']))
        ->assertOk()
        ->assertSee('Analyze website')
        ->assertSee('https://example.com');
});

test('company website analysis saves a brief from the page text', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com' => Http::response('<html><body>Finance software for operators</body></html>'),
    ]);

    BrandBriefAgent::fake([
        [
            'value_proposition' => 'Example sells finance software to operators who need fewer tools in one place.',
            'icps' => [
                ['title' => 'Ops lead', 'description' => 'Runs the daily stack.'],
                ['title' => 'CFO', 'description' => 'Owns spend and reporting.'],
            ],
        ],
    ]);

    $user = User::factory()->company()->create();
    $user->company->update(['website' => null]);

    $this->actingAs($user)
        ->post(route('onboarding.company.website'), [
            'website' => 'https://example.com',
        ])
        ->assertRedirect(route('onboarding.company'));

    $company = $user->fresh()->company;

    expect($company->website)->toBe('https://example.com')
        ->and($company->value_proposition)->toContain('finance software')
        ->and($company->icps)->toHaveCount(2)
        ->and($company->icps[0]['title'])->toBe('Ops lead');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://example.com');
    BrandBriefAgent::assertPrompted(
        fn ($prompt): bool => str_contains($prompt->prompt, 'Finance software for operators'),
    );
});

test('company website analysis does not invent ICPs when the page has no text', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://empty.test' => Http::response('<html><body>   </body></html>'),
    ]);

    BrandBriefAgent::fake()->preventStrayPrompts();

    $user = User::factory()->company()->create();
    $user->company->update(['website' => null, 'value_proposition' => null, 'icps' => null]);

    $this->actingAs($user)
        ->post(route('onboarding.company.website'), [
            'website' => 'https://empty.test',
        ])
        ->assertRedirect(route('onboarding.company'));

    $company = $user->fresh()->company;

    expect($company->website)->toBe('https://empty.test')
        ->and($company->value_proposition)->toBeNull()
        ->and($company->icps)->toBeNull();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://empty.test');
    BrandBriefAgent::assertNeverPrompted();
});

test('company can confirm the brief and open the workspace', function () {
    $user = User::factory()->company()->create();
    $user->company->update(['website' => 'https://example.com']);

    $this->actingAs($user)
        ->post(route('onboarding.company.brief'), [
            'value_proposition' => 'We sell workflow software to mid-market finance teams who need a single source of truth.',
            'icps' => [
                ['title' => 'Controller', 'description' => 'Closes the books each month.'],
            ],
        ])
        ->assertRedirect(route('company'));

    expect($user->fresh()->company->onboarded_at)->not->toBeNull()
        ->and($user->fresh()->company->icps)->toHaveCount(1);
});

test('company cannot confirm a brief before adding a website', function () {
    $user = User::factory()->company()->create();
    $user->company->update(['website' => null]);

    $this->actingAs($user)
        ->post(route('onboarding.company.brief'), [
            'value_proposition' => 'We sell workflow software to mid-market finance teams who need a single source of truth.',
            'icps' => [
                ['title' => 'Controller', 'description' => 'Closes the books each month.'],
            ],
        ]);

    expect($user->fresh()->company->onboarded_at)->toBeNull();
});

test('company cannot confirm a brief with no ICPs', function () {
    $user = User::factory()->company()->create();
    $user->company->update(['website' => 'https://example.com']);

    $this->actingAs($user)
        ->post(route('onboarding.company.brief'), [
            'value_proposition' => 'We sell workflow software to mid-market finance teams who need a single source of truth.',
            'icps' => [],
        ])
        ->assertSessionHasErrors(['icps' => 'The icps field is required.']);

    expect($user->fresh()->company->onboarded_at)->toBeNull();
});
