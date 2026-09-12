<?php

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

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
        ->and($user->creatorProfile)->not->toBeNull();

    Notification::assertSentTo($user, EmailVerificationCode::class);
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
