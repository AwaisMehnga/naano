<?php

use App\Enums\CollaborationStatus;
use App\Models\Message;
use App\Models\User;
use App\Notifications\CollaborationMessageReceived;
use Illuminate\Support\Facades\Notification;

test('companies and creators can send list and read a collaboration thread', function () {
    [$owner, $collaboration, $creatorUser] = bookedDeal();

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.messages.store', $collaboration), [
            'body' => 'Can you tighten the CTA?',
        ])
        ->assertOk()
        ->assertJsonPath('data.body', 'Can you tighten the CTA?')
        ->assertJsonPath('data.author.id', $owner->id);

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.collaborations.messages.store', $collaboration), [
            'body' => 'Will do this afternoon.',
        ])
        ->assertOk()
        ->assertJsonPath('data.body', 'Will do this afternoon.');

    $this->actingAs($owner)
        ->getJson(route('api.company.collaborations.messages.index', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.0.body', 'Can you tighten the CTA?')
        ->assertJsonPath('data.1.body', 'Will do this afternoon.')
        ->assertJsonPath('data.1.read_at', null);

    $read = $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.messages.read', $collaboration))
        ->assertOk();

    expect($read->json('data.0.read_at'))->toBeNull();
    expect($read->json('data.1.read_at'))->not->toBeNull();

    expect(Message::query()->where('author_user_id', $owner->id)->value('read_at'))->toBeNull();
});

test('other workspaces and creators cannot read a collaboration thread', function () {
    [$owner, $collaboration, $creatorUser] = bookedDeal();
    $otherCompany = User::factory()->company()->onboarded()->create();
    $otherCreator = marketplaceCreator(['display_name' => 'Other Ada']);

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.messages.store', $collaboration), [
            'body' => 'Private brief note',
        ])
        ->assertOk();

    $this->actingAs($otherCompany)
        ->getJson(route('api.company.collaborations.messages.index', $collaboration))
        ->assertNotFound();

    $this->actingAs($otherCompany)
        ->postJson(route('api.company.collaborations.messages.store', $collaboration), [
            'body' => 'Nope',
        ])
        ->assertNotFound();

    $this->actingAs($otherCreator->user)
        ->getJson(route('api.creator.collaborations.messages.index', $collaboration))
        ->assertNotFound();

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.collaborations.messages.index', $collaboration))
        ->assertOk()
        ->assertJsonPath('data.0.body', 'Private brief note');
});

test('cancelled collaborations cannot receive messages', function () {
    [$owner, $collaboration, $creatorUser] = bookedDeal();
    $collaboration->update(['status' => CollaborationStatus::Cancelled]);

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.messages.store', $collaboration), [
            'body' => 'Still here?',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('data.body.0', 'This collaboration can no longer receive messages.');

    $this->actingAs($creatorUser)
        ->getJson(route('api.creator.collaborations.messages.index', $collaboration))
        ->assertOk()
        ->assertJsonPath('data', []);
});

test('message body is required', function () {
    [$owner, $collaboration, $creatorUser] = bookedDeal();

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.messages.store', $collaboration), [])
        ->assertUnprocessable();

    $this->actingAs($creatorUser)
        ->postJson(route('api.creator.collaborations.messages.store', $collaboration), [
            'body' => '',
        ])
        ->assertUnprocessable();
});

test('sending a message stores an in-app notification without email', function () {
    Notification::fake();
    [$owner, $collaboration, $creatorUser] = bookedDeal();

    $this->actingAs($owner)
        ->postJson(route('api.company.collaborations.messages.store', $collaboration), [
            'body' => 'Hello',
        ])
        ->assertOk();

    Notification::assertSentTo(
        $creatorUser,
        CollaborationMessageReceived::class,
        fn ($n, $channels) => $channels === ['database'],
    );
});
