<?php

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('a valid email code verifies the user and continues onboarding', function () {
    Notification::fake();

    $user = User::factory()->unverified()->creator()->create();
    $user->sendEmailVerificationNotification();

    $code = Notification::sent($user, EmailVerificationCode::class)->first()->code;

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => $code])
        ->assertRedirect(route('onboarding.creator', absolute: false).'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('an invalid email code is rejected', function () {
    Notification::fake();

    $user = User::factory()->unverified()->creator()->create();
    $user->sendEmailVerificationNotification();

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.code'), ['code' => '000000'])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
