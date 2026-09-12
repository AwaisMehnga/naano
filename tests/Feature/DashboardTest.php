<?php

use App\Models\User;

test('guests are redirected to the login page from company and creator', function (string $path) {
    $this->get($path)->assertRedirect(route('login'));
})->with(['/company', '/company/anything', '/creator', '/creator/anything', '/dashboard']);

test('authenticated users can visit the company and creator dashboards', function (string $path) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get($path)
        ->assertOk();
})->with(['/company', '/company/reports', '/creator', '/creator/campaigns']);

test('dashboard redirects authenticated users to the company spa', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect('/company');
});
