<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\UpdateCompanyLeadRequest;
use App\Models\Lead;
use App\Services\CompanyLeadService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function __construct(
        private CompanyLeadService $leads,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function update(UpdateCompanyLeadRequest $request, Lead $lead): JsonResponse
    {
        return AjaxResponse::success($this->leads->update(
            $this->currentCompany->fromRequest($request),
            $lead,
            $request->validated(),
        ));
    }
}
