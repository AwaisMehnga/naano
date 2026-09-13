<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\OverviewCompanyAnalyticsRequest;
use App\Services\CompanyAnalyticsService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private CompanyAnalyticsService $analytics,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function overview(OverviewCompanyAnalyticsRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->analytics->overview(
            $this->currentCompany->fromRequest($request),
            $request->date('from'),
            $request->date('to'),
        ));
    }
}
