<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Creator\StartLinkedInVerificationRequest;
use App\Models\User;
use App\Services\CreatorProfileService;
use App\Services\LinkedIn\LinkedInProfilePresenter;
use App\Services\LinkedIn\LinkedInSyncService;
use App\Services\LinkedIn\LinkedInVerificationService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkedInController extends Controller
{
    public function __construct(
        private CreatorProfileService $profiles,
        private LinkedInVerificationService $verification,
        private LinkedInSyncService $sync,
        private LinkedInProfilePresenter $presenter,
    ) {}

    public function start(StartLinkedInVerificationRequest $request): JsonResponse
    {
        $profile = $this->profiles->profile($this->actor($request));
        $result = $this->verification->start($profile, $request->validated('linkedin_url'));

        return AjaxResponse::success($result, 'Add this code at the end of your LinkedIn headline, then verify.');
    }

    public function verify(Request $request): JsonResponse
    {
        $profile = $this->profiles->profile($this->actor($request));
        $payload = $this->verification->verify($profile);

        return AjaxResponse::success($payload, 'LinkedIn verified. You can remove the code from your headline.');
    }

    public function refresh(Request $request): JsonResponse
    {
        $profile = $this->profiles->profile($this->actor($request));
        $payload = $this->sync->refresh($profile);

        return AjaxResponse::success($payload, 'LinkedIn data refreshed.');
    }

    public function syncPosts(Request $request): JsonResponse
    {
        $profile = $this->profiles->profile($this->actor($request));
        $payload = $this->sync->syncPostsOnly($profile);

        return AjaxResponse::success($payload, 'LinkedIn posts sync started.');
    }

    public function profile(Request $request): JsonResponse
    {
        $profile = $this->profiles->profile($this->actor($request));

        return AjaxResponse::success($this->presenter->present($profile));
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
