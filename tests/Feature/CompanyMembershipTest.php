<?php

use App\Enums\CompanyMemberRole;
use App\Models\CompanyMember;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('company factory users belong to their company workspace', function () {
    $user = User::factory()->company()->create();

    expect($user->companies)->toHaveCount(1)
        ->and($user->companyMemberships)->toHaveCount(1)
        ->and($user->companyMemberships->first()->role)->toBe(CompanyMemberRole::Owner)
        ->and($user->company->members)->toHaveCount(1);
});

test('new companies receive an owner membership on registration', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@brand.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'company',
        'hear_about' => 'linkedin',
    ])->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('email', 'ada@brand.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('company'))->toBeTrue()
        ->and($user->companyMemberships)->toHaveCount(1)
        ->and($user->companyMemberships->first()->role)->toBe(CompanyMemberRole::Owner);
});

test('membership rows can point at ids that have no database foreign key', function () {
    $member = CompanyMember::factory()->create([
        'company_id' => 999001,
        'user_id' => 999002,
    ]);

    expect($member->company_id)->toBe(999001)
        ->and($member->user_id)->toBe(999002);
});
