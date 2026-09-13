<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateNotificationPreferenceRequest;
use App\Models\User;
use App\Services\NotificationPreferenceService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function __construct(private NotificationPreferenceService $preferences) {}

    public function show(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->preferences->show($this->actor($request)));
    }

    public function update(UpdateNotificationPreferenceRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->preferences->update(
            $this->actor($request),
            $request->safe()->only([
                'email_invites',
                'email_applications',
                'email_campaign_updates',
                'email_messages',
            ]),
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
