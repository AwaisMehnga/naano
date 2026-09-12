<?php

use App\Models\User;
use App\Support\AjaxResponse;

test('guests receive an ajax error from api user', function () {
    $this->getJson(route('api.user'))
        ->assertUnauthorized()
        ->assertJson([
            'status' => 'error',
            'message' => 'Unauthenticated.',
            'data' => [],
        ]);
});

test('authenticated users receive ajax success from api user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.user'))
        ->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'OK',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
});

test('ajax responses share the same payload keys', function (string $status) {
    $response = match ($status) {
        'success' => AjaxResponse::success(['ok' => true], 'Saved'),
        'error' => AjaxResponse::error('Invalid.', ['email' => ['Required']], 422),
        'failure' => AjaxResponse::failure('Boom.'),
    };

    expect($response->getData(true))->toHaveKeys(['status', 'message', 'data'])
        ->and($response->getData(true)['status'])->toBe($status);
})->with(['success', 'error', 'failure']);
