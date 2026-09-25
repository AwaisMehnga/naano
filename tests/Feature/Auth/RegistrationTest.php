<?php

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use App\Support\PasswordPolicy;
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
        ->and($user->company)->not->toBeNull();

    Notification::assertSentToTimes($user, EmailVerificationCode::class, 1);
});

test('company registration creates a company profile without Spatie roles', function () {
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
        ->and($user->company)->not->toBeNull()
        ->and($user->roles)->toHaveCount(0);
});

test('registration screen shows the password rules', function () {
    $this->get(route('register.creator'))
        ->assertOk()
        ->assertSee(PasswordPolicy::hint(), false);

    $this->get(route('register.company'))
        ->assertOk()
        ->assertSee(PasswordPolicy::hint(), false);
});

test('registration rejects a weak password without creating an account', function () {
    $this->from(route('register.creator'))
        ->post(route('register.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'role' => 'creator',
            'hear_about' => 'linkedin',
        ])
        ->assertRedirect(route('register.creator'))
        ->assertSessionHasErrors('password');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'ada@example.com']);
});

test('unverified registration can be retried with a valid password', function (string $role, string $email) {
    Notification::fake();

    $user = User::factory()->unverified()->{$role}()->create([
        'email' => $email,
        'name' => 'Old Name',
    ]);

    $this->from($role === 'creator' ? route('register.creator') : route('register.company'))
        ->post(route('register.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => $role,
            'hear_about' => 'linkedin',
        ])
        ->assertRedirect(route('verification.notice', absolute: false));

    $this->assertAuthenticatedAs($user->fresh());
    $this->assertDatabaseCount('users', 1);

    $user = $user->fresh();

    expect($user->name)->toBe('Ada Lovelace');

    if ($role === 'creator') {
        expect($user->creatorProfile)->not->toBeNull();
    } else {
        expect($user->company)->not->toBeNull();
    }

    Notification::assertSentToTimes($user, EmailVerificationCode::class, 1);
})->with([
    'creator' => ['creator', 'ada@example.com'],
    'company' => ['company', 'ada@brand.com'],
]);

test('verified emails cannot register again', function () {
    User::factory()->creator()->create([
        'email' => 'ada@example.com',
    ]);

    $this->from(route('register.creator'))
        ->post(route('register.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'creator',
            'hear_about' => 'linkedin',
        ])
        ->assertRedirect(route('register.creator'))
        ->assertSessionHasErrors([
            'email' => 'This email is already registered. Sign in or reset your password.',
        ]);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

test('registration without a role creates a user with no profile', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'hear_about' => 'linkedin',
    ])->assertRedirect(route('verification.notice', absolute: false));

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'ada@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->creatorProfile)->toBeNull()
        ->and($user->company)->toBeNull();

    Notification::assertSentToTimes($user, EmailVerificationCode::class, 1);
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
