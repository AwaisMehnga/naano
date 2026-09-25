<?php

use App\Enums\ProfileType;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use App\Support\PasswordPolicy;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertViewIs('auth.register')
        ->assertSee(PasswordPolicy::hint(), false);
});

test('legacy role register urls redirect to register', function () {
    $this->get(route('register.creator'))
        ->assertRedirect('/register');

    $this->get(route('register.company'))
        ->assertRedirect('/register');
});

test('new users can register without a profile', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'hear_about' => 'linkedin',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('email', 'ada@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Ada Lovelace')
        ->and($user->creatorProfile)->toBeNull()
        ->and($user->company)->toBeNull();

    Notification::assertSentToTimes($user, EmailVerificationCode::class, 1);
});

test('registration rejects a weak password without creating an account', function () {
    $this->from(route('register'))
        ->post(route('register.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'hear_about' => 'linkedin',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('password');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'ada@example.com']);
});

test('unverified registration can be retried with a valid password', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create([
        'email' => 'ada@example.com',
        'name' => 'Old Name',
    ]);

    $this->from(route('register'))
        ->post(route('register.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'hear_about' => 'linkedin',
        ])
        ->assertRedirect(route('verification.notice', absolute: false));

    $this->assertAuthenticatedAs($user->fresh());
    $this->assertDatabaseCount('users', 1);

    expect($user->fresh()->name)->toBe('Ada Lovelace')
        ->and($user->fresh()->creatorProfile)->toBeNull()
        ->and($user->fresh()->company)->toBeNull();

    Notification::assertSentToTimes($user->fresh(), EmailVerificationCode::class, 1);
});

test('verified emails cannot register again', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
    ]);

    $this->from(route('register'))
        ->post(route('register.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'hear_about' => 'linkedin',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors([
            'email' => 'This email is already registered. Sign in or reset your password.',
        ]);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

test('verified registration continues to profile choose', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'hear_about' => 'linkedin',
    ])->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('email', 'ada@example.com')->first();
    $code = Notification::sent($user, EmailVerificationCode::class)->last()->code;

    $this->post(route('verification.code'), [
        'code' => $code,
    ])->assertRedirect(route('profiles.choose', absolute: false).'?verified=1');
});

test('choosing creator after verify opens creator onboarding', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'hear_about' => 'linkedin',
    ]);

    $user = User::query()->where('email', 'ada@example.com')->first();
    $code = Notification::sent($user, EmailVerificationCode::class)->last()->code;

    $this->post(route('verification.code'), ['code' => $code]);

    $this->post(route('profiles.choose.store'), [
        'type' => ProfileType::Creator->value,
    ])->assertRedirect(route('onboarding.creator', absolute: false));

    expect($user->fresh()->creatorProfile)->not->toBeNull();
});

test('choosing company after verify opens company onboarding', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@brand.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'hear_about' => 'linkedin',
    ]);

    $user = User::query()->where('email', 'ada@brand.com')->first();
    $code = Notification::sent($user, EmailVerificationCode::class)->last()->code;

    $this->post(route('verification.code'), ['code' => $code]);

    $this->post(route('profiles.choose.store'), [
        'type' => ProfileType::Company->value,
    ])->assertRedirect(route('onboarding.company', absolute: false));

    expect($user->fresh()->company)->not->toBeNull()
        ->and($user->fresh()->roles)->toHaveCount(0);
});
