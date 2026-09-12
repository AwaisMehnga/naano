<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CreatorAudienceService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AudienceController extends Controller
{
    public function __construct(private CreatorAudienceService $audience) {}

    public function show(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->audience->show($this->actor($request)));
    }

    public function store(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->audience->refresh($this->actor($request)));
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
