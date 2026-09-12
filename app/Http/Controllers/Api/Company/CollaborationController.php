<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\IndexCompanyAllCollaborationsRequest;
use App\Models\Collaboration;
use App\Services\CompanyCollaborationService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollaborationController extends Controller
{
    public function __construct(
        private CompanyCollaborationService $collaborations,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function index(IndexCompanyAllCollaborationsRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->indexForCompany(
            $this->currentCompany->fromRequest($request),
            $request->validated(),
        ));
    }

    public function show(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->show(
            $this->currentCompany->fromRequest($request),
            $collaboration,
        ));
    }

    public function events(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->events(
            $this->currentCompany->fromRequest($request),
            $collaboration,
        ));
    }

    public function contract(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->contract(
            $this->currentCompany->fromRequest($request),
            $collaboration,
        ));
    }
}
