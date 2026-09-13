<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Models\Collaboration;
use App\Models\User;
use App\Services\CreatorAnalyticsService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollaborationMetricController extends Controller
{
    public function __construct(private CreatorAnalyticsService $analytics) {}

    public function show(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->analytics->collaboration(
            $this->actor($request),
            $collaboration,
        ));
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
