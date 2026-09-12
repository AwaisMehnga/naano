<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $users) {}

    /**
     * Return the authenticated user.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return AjaxResponse::error('Unauthenticated.', status: 401);
        }

        return AjaxResponse::success($this->users->current($user, $request));
    }
}
