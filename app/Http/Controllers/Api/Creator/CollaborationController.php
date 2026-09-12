<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Creator\IndexCreatorCollaborationsRequest;
use App\Models\Collaboration;
use App\Models\User;
use App\Services\CreatorOpportunityService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollaborationController extends Controller
{
    public function __construct(private CreatorOpportunityService $opportunities) {}

    public function index(IndexCreatorCollaborationsRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->opportunities->collaborations(
            $this->actor($request),
            $request->validated(),
        ));
    }

    public function show(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->opportunities->showCollaboration(
            $this->actor($request),
            $collaboration,
        ));
    }

    public function accept(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->opportunities->accept(
            $this->actor($request),
            $collaboration,
        ));
    }

    public function decline(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->opportunities->decline(
            $this->actor($request),
            $collaboration,
        ));
    }

    public function contract(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->opportunities->contract(
            $this->actor($request),
            $collaboration,
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
