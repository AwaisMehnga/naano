<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyWorkspaceService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function __construct(private CompanyWorkspaceService $workspaces) {}

    public function index(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->workspaces->index($this->actor($request), $request));
    }

    public function update(Request $request, Company $workspace): JsonResponse
    {
        return AjaxResponse::success($this->workspaces->switch($this->actor($request), $workspace, $request));
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
