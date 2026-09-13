<?php

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\CollaborationApplied;
use App\Notifications\CollaborationInvited;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

test('inviting a creator writes a database notification for that creator', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'name' => 'Q4 brief',
    ]);
    $creator = marketplaceCreator();

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.invites.store', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertOk();

    $inbox = $this->actingAs($creator->user)
        ->getJson(route('api.notifications.index'))
        ->assertOk()
        ->assertJsonPath('data.unread_count', 1)
        ->assertJsonPath('data.notifications.0.type', 'invite')
        ->assertJsonPath('data.notifications.0.data.campaign_name', 'Q4 brief');

    $this->actingAs($owner)
        ->getJson(route('api.user'))
        ->assertOk()
        ->assertJsonPath('data.unread_notifications_count', 0);

    $id = $inbox->json('data.notifications.0.id');

    $read = $this->actingAs($creator->user)
        ->postJson(route('api.notifications.read', $id))
        ->assertOk();

    expect($read->json('data.read_at'))->not->toBeNull();

    $this->actingAs($creator->user)
        ->getJson(route('api.notifications.index'))
        ->assertOk()
        ->assertJsonPath('data.unread_count', 0);
});

test('applying notifies company members and not the creator', function () {
    Queue::fake();

    [$owner, $company, $member] = companyWithMember();
    $campaign = Campaign::factory()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Active,
        'name' => 'Open brief',
    ]);
    $creator = marketplaceCreator();
    fakeCampaignFit();

    $this->actingAs($creator->user)
        ->postJson(route('api.creator.opportunities.apply', $campaign))
        ->assertOk();

    $this->actingAs($owner)
        ->getJson(route('api.notifications.index'))
        ->assertOk()
        ->assertJsonPath('data.unread_count', 1)
        ->assertJsonPath('data.notifications.0.type', 'application')
        ->assertJsonPath('data.notifications.0.data.href', '/collaboration?campaign='.$campaign->id);

    $this->actingAs($member)
        ->getJson(route('api.notifications.index'))
        ->assertOk()
        ->assertJsonPath('data.unread_count', 1);

    $this->actingAs($creator->user)
        ->getJson(route('api.notifications.index'))
        ->assertOk()
        ->assertJsonPath('data.unread_count', 0);
});

test('marking all notifications read clears the unread count', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.invites.store', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertOk();

    $this->actingAs($creator->user)
        ->postJson(route('api.notifications.read-all'))
        ->assertOk()
        ->assertJsonPath('data.unread_count', 0);
});

test('a user cannot mark another users notification as read', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $other = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.invites.store', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertOk();

    $id = $this->actingAs($creator->user)
        ->getJson(route('api.notifications.index'))
        ->json('data.notifications.0.id');

    $this->actingAs($other)
        ->postJson(route('api.notifications.read', $id))
        ->assertNotFound();
});

test('muted invite email still stores a database notification', function () {
    Notification::fake();

    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);
    $creator = marketplaceCreator();

    NotificationPreference::factory()->create([
        'user_id' => $creator->user_id,
        'email_invites' => false,
        'email_applications' => true,
        'email_campaign_updates' => true,
        'email_messages' => true,
    ]);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.invites.store', $campaign), [
            'creator_profile_id' => $creator->id,
        ])
        ->assertOk();

    Notification::assertSentTo(
        $creator->user,
        CollaborationInvited::class,
        function (CollaborationInvited $notification, array $channels): bool {
            return $channels === ['database'];
        },
    );
    Notification::assertNotSentTo($owner, CollaborationApplied::class);
});
