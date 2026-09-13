<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CreatorAccountService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectController extends Controller
{
    public function __construct(private CreatorAccountService $accounts) {}

    public function show(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->accounts->connect($this->actor($request)));
    }

    public function onboarding(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->accounts->startOnboarding($this->actor($request)));
    }

    public function dashboard(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->accounts->dashboard($this->actor($request)));
    }

    private function actor(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
