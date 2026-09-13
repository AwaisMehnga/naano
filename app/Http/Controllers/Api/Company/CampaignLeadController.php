<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\StoreCompanyLeadRequest;
use App\Models\Campaign;
use App\Services\CompanyLeadService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignLeadController extends Controller
{
    public function __construct(
        private CompanyLeadService $leads,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function index(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->leads->index(
            $this->currentCompany->fromRequest($request),
            $campaign,
        ));
    }

    public function store(StoreCompanyLeadRequest $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->leads->store(
            $this->currentCompany->fromRequest($request),
            $campaign,
            $request->validated(),
        ));
    }
}
