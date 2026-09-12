<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Creator\UpdateCreatorNichesRequest;
use App\Models\User;
use App\Services\CreatorProfileService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;

class NicheController extends Controller
{
    public function __construct(private CreatorProfileService $profiles) {}

    public function update(UpdateCreatorNichesRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Unauthenticated.');
        }

        return AjaxResponse::success($this->profiles->replaceNiches(
            $user,
            $request->validated('niche_ids'),
        ));
    }
}
