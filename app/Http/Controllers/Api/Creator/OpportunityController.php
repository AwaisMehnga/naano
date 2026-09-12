<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Services\CreatorOpportunityService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function __construct(private CreatorOpportunityService $opportunities) {}

    public function index(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->opportunities->opportunities($this->actor($request)));
    }

    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->opportunities->showOpportunity(
            $this->actor($request),
            $campaign,
        ));
    }

    public function apply(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->opportunities->apply(
            $this->actor($request),
            $campaign,
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
