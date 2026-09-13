<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\UpdateCompanyTrackingLinkRequest;
use App\Models\TrackingLink;
use App\Services\CurrentCompanyService;
use App\Services\TrackingLinkService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingLinkController extends Controller
{
    public function __construct(
        private TrackingLinkService $tracking,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function update(UpdateCompanyTrackingLinkRequest $request, TrackingLink $trackingLink): JsonResponse
    {
        return AjaxResponse::success($this->tracking->update(
            $this->currentCompany->fromRequest($request),
            $trackingLink,
            $request->validated(),
        ));
    }

    public function destroy(Request $request, TrackingLink $trackingLink): JsonResponse
    {
        $this->tracking->destroy(
            $this->currentCompany->fromRequest($request),
            $trackingLink,
        );

        return AjaxResponse::success([]);
    }
}
