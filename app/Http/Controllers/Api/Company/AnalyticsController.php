<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Services\CompanyAnalyticsService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private CompanyAnalyticsService $analytics,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->analytics->overview(
            $this->currentCompany->fromRequest($request),
        ));
    }
}
