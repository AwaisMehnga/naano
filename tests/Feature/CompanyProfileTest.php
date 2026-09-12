<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('owners can view and update the company profile', function () {
    $user = User::factory()->company()->onboarded()->create();
    $company = $user->companies()->first();
    $company->update(['name' => 'Old Co']);

    $this->actingAs($user)
        ->getJson(route('api.company.profile.show'))
        ->assertOk()
        ->assertJsonPath('data.name', 'Old Co')
        ->assertJsonPath('data.can_manage_money', true);

    $this->actingAs($user)
        ->patchJson(route('api.company.profile.update'), [
            'name' => 'Acme',
            'billing_email' => 'finance@acme.test',
            'country' => 'FR',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Acme')
        ->assertJsonPath('data.billing_email', 'finance@acme.test');

    expect($company->fresh()->name)->toBe('Acme')
        ->and($company->fresh()->billing_email)->toBe('finance@acme.test');
});

test('members can update profile fields but not billing email', function () {
    [$owner, $company, $member] = companyWithMember();

    $this->actingAs($member)
        ->withHeaders(['X-Company-Id' => (string) $company->id])
        ->patchJson(route('api.company.profile.update'), [
            'name' => 'Member Rename',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Member Rename')
        ->assertJsonPath('data.can_manage_money', false);

    $this->actingAs($member)
        ->withHeaders(['X-Company-Id' => (string) $company->id])
        ->patchJson(route('api.company.profile.update'), [
            'billing_email' => 'stolen@acme.test',
        ])
        ->assertForbidden();

    expect($company->fresh()->billing_email)->not->toBe('stolen@acme.test');
});

test('company logos appear on the profile and current user payloads', function () {
    Storage::fake('public');

    $user = User::factory()->company()->onboarded()->create();
    $company = $user->companies()->first();

    $this->actingAs($user)
        ->patch(route('api.company.profile.update'), [
            'logo' => fakePng('logo.png'),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    $logoPath = $company->fresh()->logo_path;

    expect($logoPath)->not->toBeNull();
    Storage::disk('public')->assertExists($logoPath);

    $this->actingAs($user)
        ->getJson(route('api.company.profile.show'))
        ->assertOk()
        ->assertJsonPath('data.logo_url', Storage::disk('public')->url($logoPath));

    $this->actingAs($user)
        ->getJson(route('api.user'))
        ->assertOk()
        ->assertJsonPath('data.avatar', Storage::disk('public')->url($logoPath));

    $this->actingAs($user)
        ->get(route('company'))
        ->assertOk()
        ->assertSee(basename($logoPath), false);
});

test('companies can remove their logo', function () {
    Storage::fake('public');

    $user = User::factory()->company()->onboarded()->create();
    $company = $user->companies()->first();
    $path = 'companies/'.$company->id.'/logo.png';
    Storage::disk('public')->put($path, 'fake');
    $company->update(['logo_path' => $path]);

    $this->actingAs($user)
        ->patchJson(route('api.company.profile.update'), [
            'remove_logo' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.logo_url', null);

    expect($company->fresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
