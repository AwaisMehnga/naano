<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CompanyAnalyticsService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignReportController extends Controller
{
    public function __construct(
        private CompanyAnalyticsService $analytics,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->analytics->report(
            $this->currentCompany->fromRequest($request),
            $campaign,
        ));
    }
}
