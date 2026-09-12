<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('user', [UserController::class, 'show'])->name('user');

    Route::middleware(['verified', 'role:company', 'onboarded'])->prefix('company')->name('company.')->group(function () {
        Route::get('ping', [UserController::class, 'show'])->name('ping');
    });

    Route::middleware(['verified', 'role:creator', 'onboarded'])->prefix('creator')->name('creator.')->group(function () {
        Route::get('ping', [UserController::class, 'show'])->name('ping');
    });
});
