<?php

use App\Models\Campaign;
use App\Models\User;

test('company users can search their campaigns by name', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $match = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'name' => 'Q4 Pipeline',
    ]);
    Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'name' => 'Hiring brand',
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.index', ['q' => 'Pipeline']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('data.0.name', 'Q4 Pipeline');
});

test('company users do not see another workspace campaigns', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $other = User::factory()->company()->onboarded()->create();
    Campaign::factory()->create([
        'company_id' => $other->company->id,
        'created_by_user_id' => $other->id,
        'name' => 'Secret launch',
    ]);

    $this->actingAs($owner)
        ->getJson(route('api.company.campaigns.index', ['q' => 'Secret']))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('guests and creators cannot search company campaigns', function () {
    $creator = User::factory()->creator()->onboarded()->create();

    $this->getJson(route('api.company.campaigns.index'))
        ->assertUnauthorized();

    $this->actingAs($creator)
        ->getJson(route('api.company.campaigns.index'))
        ->assertForbidden();
});
