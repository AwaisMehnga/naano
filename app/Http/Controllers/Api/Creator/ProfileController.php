<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Creator\UpdateCreatorProfileRequest;
use App\Models\User;
use App\Services\CreatorProfileService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private CreatorProfileService $profiles) {}

    public function show(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->profiles->show($this->actor($request)));
    }

    public function update(UpdateCreatorProfileRequest $request): JsonResponse
    {
        $data = $request->safe()->only([
            'display_name',
            'linkedin_url',
            'headline',
            'bio',
            'country',
        ]);

        return AjaxResponse::success($this->profiles->update(
            $this->actor($request),
            $data,
            $request->file('photo'),
            $request->boolean('remove_photo'),
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
