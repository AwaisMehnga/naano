<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProfileType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ActivateProfileRequest;
use App\Models\User;
use App\Services\ProfileService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $profiles) {}

    public function index(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->profiles->index($this->actor($request), $request));
    }

    public function storeCreator(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->profiles->createCreator($this->actor($request), $request));
    }

    public function storeCompany(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->profiles->createCompany($this->actor($request), $request));
    }

    public function updateActive(ActivateProfileRequest $request): JsonResponse
    {
        $type = $request->enum('type', ProfileType::class);

        if (! $type instanceof ProfileType) {
            return AjaxResponse::error('Invalid profile type.', status: 422);
        }

        return AjaxResponse::success($this->profiles->activate($this->actor($request), $request, $type));
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
