<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\StoreCompanyTrackingLinkRequest;
use App\Models\Campaign;
use App\Services\CurrentCompanyService;
use App\Services\TrackingLinkService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignTrackingLinkController extends Controller
{
    public function __construct(
        private TrackingLinkService $tracking,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function index(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->tracking->index(
            $this->currentCompany->fromRequest($request),
            $campaign,
        ));
    }

    public function store(StoreCompanyTrackingLinkRequest $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->tracking->store(
            $this->currentCompany->fromRequest($request),
            $campaign,
            $request->validated(),
        ));
    }
}
