<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('creators can upload and delete media', function () {
    Storage::fake('public');

    $creator = marketplaceCreator();

    $upload = $this->actingAs($creator->user)
        ->postJson(route('api.creator.media.store'), [
            'file' => fakePng('shot.png'),
        ])
        ->assertOk()
        ->assertJsonPath('data.kind', 'image');

    $mediaId = $upload->json('data.id');

    expect(Media::query()->whereKey($mediaId)->exists())->toBeTrue();

    $this->actingAs($creator->user)
        ->deleteJson(route('api.creator.media.destroy', $mediaId))
        ->assertOk();

    expect(Media::query()->whereKey($mediaId)->exists())->toBeFalse();
});

test('creators can attach media to a draft post', function () {
    Storage::fake('public');

    [, $collaboration, $creatorUser] = bookedDeal();
    $post = $collaboration->posts()->first();

    $upload = $this->actingAs($creatorUser)
        ->postJson(route('api.creator.media.store'), [
            'file' => fakePng('hero.png'),
        ])
        ->assertOk();

    $mediaId = $upload->json('data.id');

    $this->actingAs($creatorUser)
        ->patchJson(route('api.creator.posts.update', $post), [
            'body' => 'Post with a photo.',
            'media_ids' => [$mediaId],
        ])
        ->assertOk()
        ->assertJsonPath('data.media.0.id', $mediaId)
        ->assertJsonPath('data.body', 'Post with a photo.');

    expect(Media::query()->find($mediaId)?->mediable_id)->toBe($post->id);
});

test('company users cannot upload creator media', function () {
    Storage::fake('public');

    $owner = User::factory()->company()->onboarded()->create();

    $this->actingAs($owner)
        ->postJson(route('api.creator.media.store'), [
            'file' => fakePng('nope.png'),
        ])
        ->assertForbidden();
});
