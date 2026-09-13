<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\StoreCompanyCollaborationMessageRequest;
use App\Models\Collaboration;
use App\Models\User;
use App\Services\CollaborationMessageService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollaborationMessageController extends Controller
{
    public function __construct(
        private CollaborationMessageService $messages,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function index(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->messages->indexForCompany(
            $this->currentCompany->fromRequest($request),
            $collaboration,
        ));
    }

    public function store(StoreCompanyCollaborationMessageRequest $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->messages->storeForCompany(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $collaboration,
            $request->validated('body'),
        ));
    }

    public function read(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->messages->markReadForCompany(
            $this->currentCompany->fromRequest($request),
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
