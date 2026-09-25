<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Creator\OverviewCreatorAnalyticsRequest;
use App\Models\User;
use App\Services\CreatorAnalyticsService;
use App\Services\LinkedIn\LinkedInSyncService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private CreatorAnalyticsService $analytics,
        private LinkedInSyncService $linkedin,
    ) {}

    public function overview(OverviewCreatorAnalyticsRequest $request): JsonResponse
    {
        $user = $this->actor($request);
        $profile = $user->creatorProfile;

        if ($profile !== null) {
            $this->linkedin->deferPostsSync($profile);
        }

        return AjaxResponse::success($this->analytics->overview(
            $user,
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
