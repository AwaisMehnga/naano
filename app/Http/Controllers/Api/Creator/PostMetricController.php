<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Services\CreatorAnalyticsService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostMetricController extends Controller
{
    public function __construct(private CreatorAnalyticsService $analytics) {}

    public function show(Request $request, Post $post): JsonResponse
    {
        return AjaxResponse::success($this->analytics->post(
            $this->actor($request),
            $post,
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
