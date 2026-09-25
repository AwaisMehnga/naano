<?php

use App\Http\Controllers\Auth\EmailCodeController;
use App\Http\Controllers\Onboarding\CompanyOnboardingController;
use App\Http\Controllers\Onboarding\CreatorOnboardingController;
use App\Http\Controllers\ProfileChooseController;
use App\Http\Controllers\TrackingPixelController;
use App\Http\Controllers\TrackingRedirectController;
use App\Support\HomeRedirect;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('t/{slug}', [TrackingRedirectController::class, 'show'])
    ->where('slug', '[A-Za-z0-9]+')
    ->name('tracking.redirect');

Route::get('pixel.js', [TrackingPixelController::class, 'show'])->name('tracking.pixel');

Route::middleware('guest')->group(function () {
    Route::view('register/creator', 'auth.register-form', ['role' => 'creator'])->name('register.creator');
    Route::view('register/company', 'auth.register-form', ['role' => 'company'])->name('register.company');
});

Route::post('email/verify-code', [EmailCodeController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.code');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', function () {
        return redirect(HomeRedirect::path(request()->user(), request()));
    })->name('dashboard');

    Route::middleware('verified')->group(function () {
        Route::get('profiles/choose', [ProfileChooseController::class, 'show'])->name('profiles.choose');
        Route::post('profiles/choose', [ProfileChooseController::class, 'store'])->name('profiles.choose.store');
    });

    Route::middleware(['verified', 'profile:creator'])->group(function () {
        Route::get('onboarding/creator', [CreatorOnboardingController::class, 'show'])->name('onboarding.creator');
        Route::post('onboarding/creator/linkedin', [CreatorOnboardingController::class, 'linkedin'])->name('onboarding.creator.linkedin');
        Route::post('onboarding/creator/industries', [CreatorOnboardingController::class, 'industries'])->name('onboarding.creator.industries');
        Route::post('onboarding/creator/offer', [CreatorOnboardingController::class, 'offer'])->name('onboarding.creator.offer');
        Route::post('onboarding/creator/complete', [CreatorOnboardingController::class, 'complete'])->name('onboarding.creator.complete');
    });

    Route::middleware(['verified', 'profile:company'])->group(function () {
        Route::get('onboarding/company', [CompanyOnboardingController::class, 'show'])->name('onboarding.company');
        Route::post('onboarding/company/website', [CompanyOnboardingController::class, 'website'])
            ->middleware('throttle:5,1')
            ->name('onboarding.company.website');
        Route::post('onboarding/company/brief', [CompanyOnboardingController::class, 'brief'])->name('onboarding.company.brief');
    });

    Route::middleware(['verified', 'profile:company', 'onboarded'])->group(function () {
        Route::view('/company/{any?}', 'spa.company')
            ->where('any', '.*')
            ->name('company');
    });

    Route::middleware(['verified', 'profile:creator', 'onboarded'])->group(function () {
        Route::view('/creator/{any?}', 'spa.creator')
            ->where('any', '.*')
            ->name('creator');
    });
});

require __DIR__.'/settings.php';
