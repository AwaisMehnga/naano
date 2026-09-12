<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Collaboration;
use App\Services\CompanyCollaborationService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollaborationActionController extends Controller
{
    public function __construct(
        private CompanyCollaborationService $collaborations,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function select(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->select(
            $this->currentCompany->fromRequest($request),
            $collaboration,
        ));
    }

    public function cancel(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->cancel(
            $this->currentCompany->fromRequest($request),
            $collaboration,
        ));
    }
}
