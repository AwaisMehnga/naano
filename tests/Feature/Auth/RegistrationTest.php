<?php

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertViewIs('auth.register');
});

test('creator registration screen can be rendered', function () {
    $this->get(route('register.creator'))
        ->assertOk()
        ->assertViewIs('auth.register-form');
});

test('new creators can register', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'creator',
        'hear_about' => 'linkedin',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('email', 'ada@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Ada Lovelace')
        ->and($user->hasRole('creator'))->toBeTrue()
        ->and($user->creatorProfile)->not->toBeNull()
        ->and($user->creatorProfile->display_name)->toBe('Ada Lovelace');

    Notification::assertSentToTimes($user, EmailVerificationCode::class, 1);
});

test('new companies can register', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@brand.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'company',
        'hear_about' => 'linkedin',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('email', 'ada@brand.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Ada Lovelace')
        ->and($user->hasRole('company'))->toBeTrue()
        ->and($user->company)->not->toBeNull();

    Notification::assertSentToTimes($user, EmailVerificationCode::class, 1);
});

test('company registration assigns the company role even when roles were not seeded', function () {
    Notification::fake();

    Role::query()->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

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
        ->and($user->company)->not->toBeNull();
});

test('registration requires a role', function () {
    $this->post(route('register.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'hear_about' => 'linkedin',
    ])->assertSessionHasErrors('role');

    $this->assertGuest();
});

test('verified creator registration continues to creator onboarding', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'creator',
        'hear_about' => 'linkedin',
    ])->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('email', 'ada@example.com')->first();
    $code = Notification::sent($user, EmailVerificationCode::class)->last()->code;

    $this->post(route('verification.code'), [
        'code' => $code,
    ])->assertRedirect(route('onboarding.creator', absolute: false).'?verified=1');
});

test('verified company registration continues to company onboarding', function () {
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
    $code = Notification::sent($user, EmailVerificationCode::class)->last()->code;

    $this->post(route('verification.code'), [
        'code' => $code,
    ])->assertRedirect(route('onboarding.company', absolute: false).'?verified=1');
});
