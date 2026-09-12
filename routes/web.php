<?php

use App\Http\Controllers\Auth\EmailCodeController;
use App\Http\Controllers\Onboarding\CompanyOnboardingController;
use App\Http\Controllers\Onboarding\CreatorOnboardingController;
use App\Support\HomeRedirect;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::view('register/creator', 'auth.register-form', ['role' => 'creator'])->name('register.creator');
    Route::view('register/company', 'auth.register-form', ['role' => 'company'])->name('register.company');
});

Route::post('email/verify-code', [EmailCodeController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.code');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', function () {
        return redirect(HomeRedirect::path(request()->user()));
    })->name('dashboard');

    Route::middleware(['verified', 'role:creator'])->group(function () {
        Route::get('onboarding/creator', [CreatorOnboardingController::class, 'show'])->name('onboarding.creator');
        Route::post('onboarding/creator/linkedin', [CreatorOnboardingController::class, 'linkedin'])->name('onboarding.creator.linkedin');
        Route::post('onboarding/creator/industries', [CreatorOnboardingController::class, 'industries'])->name('onboarding.creator.industries');
        Route::post('onboarding/creator/offer', [CreatorOnboardingController::class, 'offer'])->name('onboarding.creator.offer');
        Route::post('onboarding/creator/complete', [CreatorOnboardingController::class, 'complete'])->name('onboarding.creator.complete');
    });

    Route::middleware(['verified', 'role:company'])->group(function () {
        Route::get('onboarding/company', [CompanyOnboardingController::class, 'show'])->name('onboarding.company');
        Route::post('onboarding/company/website', [CompanyOnboardingController::class, 'website'])
            ->middleware('throttle:5,1')
            ->name('onboarding.company.website');
        Route::post('onboarding/company/brief', [CompanyOnboardingController::class, 'brief'])->name('onboarding.company.brief');
    });

    Route::middleware(['verified', 'role:company', 'onboarded'])->group(function () {
        Route::view('/company/{any?}', 'spa.company')
            ->where('any', '.*')
            ->name('company');
    });

    Route::middleware(['verified', 'role:creator', 'onboarded'])->group(function () {
        Route::view('/creator/{any?}', 'spa.creator')
            ->where('any', '.*')
            ->name('creator');
    });
});

require __DIR__.'/settings.php';
