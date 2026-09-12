<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::redirect('dashboard', '/company')->name('dashboard');

    Route::view('/company/{any?}', 'spa.company')
        ->where('any', '.*')
        ->name('company');

    Route::view('/creator/{any?}', 'spa.creator')
        ->where('any', '.*')
        ->name('creator');
});

require __DIR__.'/settings.php';
