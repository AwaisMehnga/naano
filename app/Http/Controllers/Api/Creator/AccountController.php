<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Creator\DeleteCreatorAccountRequest;
use App\Models\User;
use App\Services\CreatorAccountService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function __construct(private CreatorAccountService $accounts) {}

    public function destroy(DeleteCreatorAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Unauthenticated.');
        }

        $this->accounts->destroy($user, $request);

        return AjaxResponse::success([]);
    }
}
