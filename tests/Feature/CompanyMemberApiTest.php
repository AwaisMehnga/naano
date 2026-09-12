<?php

use App\Enums\CompanyMemberRole;
use App\Models\CompanyInvite;
use App\Models\CompanyMember;
use App\Models\User;
use App\Notifications\CompanyMemberInvite;
use Illuminate\Support\Facades\Notification;

test('owners can invite change role and remove members', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $invitee = User::factory()->company()->onboarded()->create();

    $this->actingAs($owner)
        ->postJson(route('api.company.members.store'), [
            'email' => $invitee->email,
            'role' => 'member',
        ])
        ->assertOk()
        ->assertJsonPath('data.email', $invitee->email)
        ->assertJsonPath('data.role', 'member');

    $memberId = CompanyMember::query()
        ->where('user_id', $invitee->id)
        ->where('company_id', $owner->companies()->first()->id)
        ->value('id');

    $this->actingAs($owner)
        ->patchJson(route('api.company.members.update', $memberId), [
            'role' => 'owner',
        ])
        ->assertOk()
        ->assertJsonPath('data.role', 'owner');

    $this->actingAs($owner)
        ->patchJson(route('api.company.members.update', $memberId), [
            'role' => 'member',
        ])
        ->assertOk();

    $this->actingAs($owner)
        ->deleteJson(route('api.company.members.destroy', $memberId))
        ->assertOk();

    expect(CompanyMember::query()->where('id', $memberId)->exists())->toBeFalse();
});

test('members cannot invite or change roles', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $company = $owner->companies()->first();
    $member = User::factory()->company()->onboarded()->create();
    $other = User::factory()->company()->onboarded()->create();

    CompanyMember::factory()->create([
        'company_id' => $company->id,
        'user_id' => $member->id,
        'role' => CompanyMemberRole::Member,
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->withHeaders(['X-Company-Id' => (string) $company->id])
        ->postJson(route('api.company.members.store'), [
            'email' => $other->email,
        ])
        ->assertForbidden();
});

test('the last owner cannot be removed or demoted', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $membership = $owner->companyMemberships()->first();

    $this->actingAs($owner)
        ->patchJson(route('api.company.members.update', $membership->id), [
            'role' => 'member',
        ])
        ->assertUnprocessable();

    $this->actingAs($owner)
        ->deleteJson(route('api.company.members.destroy', $membership->id))
        ->assertUnprocessable();
});

test('unknown emails receive an invite instead of being rejected', function () {
    Notification::fake();

    $owner = User::factory()->company()->onboarded()->create();
    $company = $owner->companies()->first();

    $this->actingAs($owner)
        ->postJson(route('api.company.members.store'), [
            'email' => 'phuski@yopmail.com',
            'role' => 'member',
        ])
        ->assertOk()
        ->assertJsonPath('data.email', 'phuski@yopmail.com')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.joined_at', null);

    $invite = CompanyInvite::query()
        ->where('company_id', $company->id)
        ->where('email', 'phuski@yopmail.com')
        ->first();

    expect($invite)->not->toBeNull()
        ->and($invite->role)->toBe(CompanyMemberRole::Member)
        ->and($invite->accepted_at)->toBeNull();

    $this->actingAs($owner)
        ->getJson(route('api.company.members.index'))
        ->assertOk()
        ->assertJsonFragment([
            'email' => 'phuski@yopmail.com',
            'status' => 'pending',
        ]);

    Notification::assertSentOnDemand(
        CompanyMemberInvite::class,
        function (CompanyMemberInvite $notification, array $channels, object $notifiable): bool {
            return ($notifiable->routes['mail'] ?? null) === 'phuski@yopmail.com';
        },
    );
});

test('creators cannot be invited as company members', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $creator = User::factory()->creator()->onboarded()->create();

    $this->actingAs($owner)
        ->postJson(route('api.company.members.store'), [
            'email' => $creator->email,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This email is registered as a creator.');
});
