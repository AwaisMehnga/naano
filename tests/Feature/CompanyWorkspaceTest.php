<?php

use App\Enums\CompanyMemberRole;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;

test('owners can list and switch workspaces', function () {
    $user = User::factory()->company()->onboarded()->create();
    $first = $user->companies()->first();
    $second = Company::factory()->create([
        'user_id' => $user->id,
        'name' => 'Second workspace',
        'onboarded_at' => now(),
    ]);
    CompanyMember::factory()->create([
        'company_id' => $second->id,
        'user_id' => $user->id,
        'role' => CompanyMemberRole::Owner,
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->getJson(route('api.company.workspaces.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->actingAs($user)
        ->patchJson(route('api.company.workspaces.update', $second))
        ->assertOk()
        ->assertJsonPath('data.id', $second->id)
        ->assertJsonPath('data.is_current', true);

    $this->actingAs($user)
        ->getJson(route('api.user'))
        ->assertJsonPath('data.current_company_id', $second->id);

    expect($first->id)->not->toBe($second->id);
});

test('x company id cannot access another workspace', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $other = User::factory()->company()->onboarded()->create();
    $foreign = $other->companies()->first();

    $this->actingAs($owner)
        ->withHeaders(['X-Company-Id' => (string) $foreign->id])
        ->getJson(route('api.company.profile.show'))
        ->assertForbidden();
});

test('members cannot patch another company’s icp by id', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $company = $owner->companies()->first();
    $icp = $company->companyIcps()->create([
        'title' => 'Secret',
        'description' => 'Do not leak.',
        'sort_order' => 0,
    ]);

    $stranger = User::factory()->company()->onboarded()->create();

    $this->actingAs($stranger)
        ->patchJson(route('api.company.icps.update', $icp), [
            'title' => 'Hacked',
        ])
        ->assertNotFound();
});
