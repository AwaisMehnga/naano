<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\CompanyAnalyticsService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostMetricController extends Controller
{
    public function __construct(
        private CompanyAnalyticsService $analytics,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function show(Request $request, Post $post): JsonResponse
    {
        return AjaxResponse::success($this->analytics->post(
            $this->currentCompany->fromRequest($request),
            $post,
        ));
    }
}
