<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CreatorAnalyticsService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private CreatorAnalyticsService $analytics) {}

    public function overview(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->analytics->overview($this->actor($request)));
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
