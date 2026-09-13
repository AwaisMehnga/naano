<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationInboxService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function __construct(private NotificationInboxService $inbox) {}

    public function index(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->inbox->index($this->actor($request)));
    }

    public function read(Request $request, DatabaseNotification $notification): JsonResponse
    {
        return AjaxResponse::success($this->inbox->markRead(
            $this->actor($request),
            $notification,
        ));
    }

    public function readAll(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->inbox->markAllRead($this->actor($request)));
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
