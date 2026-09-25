<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Creator\OverviewCreatorAnalyticsRequest;
use App\Models\User;
use App\Services\CreatorAnalyticsService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(private CreatorAnalyticsService $analytics) {}

    public function overview(OverviewCreatorAnalyticsRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->analytics->overview(
            $this->actor($request),
            $request->date('from'),
            $request->date('to'),
        ));
    }

    private function actor(OverviewCreatorAnalyticsRequest $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
