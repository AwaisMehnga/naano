<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\UpdateCompanyAudienceRequest;
use App\Services\CompanyAudienceService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AudienceController extends Controller
{
    public function __construct(
        private CompanyAudienceService $audience,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->audience->show($this->currentCompany->fromRequest($request)));
    }

    public function update(UpdateCompanyAudienceRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->audience->update(
            $this->currentCompany->fromRequest($request),
            $request->validated(),
        ));
    }
}
