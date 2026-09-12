<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\IndexCompanyCampaignsRequest;
use App\Services\CompanyCampaignService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    public function __construct(
        private CompanyCampaignService $campaigns,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function index(IndexCompanyCampaignsRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->campaigns->index(
            $this->currentCompany->fromRequest($request),
            $request->validated(),
        ));
    }
}
