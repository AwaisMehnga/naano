<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('user', [UserController::class, 'show'])->name('user');

    Route::prefix('company')->name('company.')->group(function () {
        // Company dashboard APIs
    });

    Route::prefix('creator')->name('creator.')->group(function () {
        // Creator dashboard APIs
    });
});
