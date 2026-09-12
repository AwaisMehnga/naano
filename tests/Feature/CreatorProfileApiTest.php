<?php

use App\Models\Niche;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('creators can view and update their profile', function () {
    $user = User::factory()->creator()->onboarded()->create();
    $user->creatorProfile->update([
        'display_name' => 'Old Name',
        'headline' => 'Old headline',
        'linkedin_url' => 'https://www.linkedin.com/in/old',
        'country' => 'FR',
    ]);

    $this->actingAs($user)
        ->getJson(route('api.creator.profile.show'))
        ->assertOk()
        ->assertJsonPath('data.display_name', 'Old Name');

    $this->actingAs($user)
        ->patchJson(route('api.creator.profile.update'), [
            'display_name' => 'Ada',
            'headline' => 'B2B creator',
            'country' => 'DE',
        ])
        ->assertOk()
        ->assertJsonPath('data.display_name', 'Ada')
        ->assertJsonPath('data.country', 'DE');
});

test('creators can replace niches', function () {
    $user = User::factory()->creator()->onboarded()->create();
    $kept = Niche::factory()->create(['name' => 'SaaS', 'slug' => 'saas']);
    $next = Niche::factory()->create(['name' => 'AI', 'slug' => 'ai']);
    $drop = Niche::factory()->create(['name' => 'HR', 'slug' => 'hr']);

    $user->creatorProfile->niches()->attach([$kept->id, $drop->id]);

    $this->actingAs($user)
        ->putJson(route('api.creator.niches.update'), [
            'niche_ids' => [$kept->id, $next->id],
        ])
        ->assertOk()
        ->assertJsonCount(2, 'data.niches');

    $ids = collect($this->actingAs($user)->getJson(route('api.creator.profile.show'))->json('data.niches'))
        ->pluck('id')
        ->all();

    expect($ids)->toContain($kept->id, $next->id)
        ->and($ids)->not->toContain($drop->id);
});

test('niches lookup returns active niches', function () {
    $user = User::factory()->creator()->onboarded()->create();
    Niche::factory()->create(['name' => 'Fintech', 'slug' => 'fintech', 'is_active' => true]);
    Niche::factory()->create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);

    $this->actingAs($user)
        ->getJson(route('api.niches.index'))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Fintech'])
        ->assertJsonMissing(['name' => 'Hidden']);
});

test('creator photos appear on the profile and current user payloads', function () {
    Storage::fake('public');

    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->patch(route('api.creator.profile.update'), [
            'photo' => fakePng('me.png'),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    $photoUrl = $user->creatorProfile()->value('photo_path');

    expect($photoUrl)->not->toBeNull();
    Storage::disk('public')->assertExists($photoUrl);

    $this->actingAs($user)
        ->getJson(route('api.creator.profile.show'))
        ->assertOk()
        ->assertJsonPath('data.photo_url', Storage::disk('public')->url($photoUrl));

    $this->actingAs($user)
        ->getJson(route('api.user'))
        ->assertOk()
        ->assertJsonPath('data.avatar', Storage::disk('public')->url($photoUrl));

    $this->actingAs($user)
        ->get(route('creator'))
        ->assertOk()
        ->assertSee(basename($photoUrl), false);
});

test('creators can remove their profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->creator()->onboarded()->create();
    $path = 'creators/'.$user->creatorProfile->id.'/me.png';
    Storage::disk('public')->put($path, 'fake');
    $user->creatorProfile->update(['photo_path' => $path]);

    $this->actingAs($user)
        ->patchJson(route('api.creator.profile.update'), [
            'remove_photo' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.photo_url', null);

    expect($user->creatorProfile->fresh()->photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);

    $this->actingAs($user)
        ->getJson(route('api.user'))
        ->assertOk()
        ->assertJsonPath('data.avatar', null);
});
