<?php

use App\Models\User;

test('notification preferences default to all email toggles on', function () {
    $user = User::factory()->company()->onboarded()->create();

    $this->actingAs($user)
        ->getJson(route('api.notification-preferences.show'))
        ->assertOk()
        ->assertJsonPath('data.email_invites', true)
        ->assertJsonPath('data.email_applications', true)
        ->assertJsonPath('data.email_campaign_updates', true)
        ->assertJsonPath('data.email_messages', true);
});

test('users can update notification email toggles', function () {
    $user = User::factory()->creator()->onboarded()->create();

    $this->actingAs($user)
        ->putJson(route('api.notification-preferences.update'), [
            'email_invites' => false,
            'email_applications' => true,
            'email_campaign_updates' => false,
            'email_messages' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.email_invites', false)
        ->assertJsonPath('data.email_campaign_updates', false)
        ->assertJsonPath('data.email_messages', true);

    $this->actingAs($user)
        ->getJson(route('api.notification-preferences.show'))
        ->assertOk()
        ->assertJsonPath('data.email_invites', false)
        ->assertJsonPath('data.email_campaign_updates', false);
});
