<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\IndexCompanyCampaignsRequest;
use App\Http\Requests\Api\Company\StoreCompanyCampaignRequest;
use App\Http\Requests\Api\Company\UpdateCompanyCampaignRequest;
use App\Models\Campaign;
use App\Models\User;
use App\Services\CompanyCampaignService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function store(StoreCompanyCampaignRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->campaigns->store(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $request->validated(),
        ));
    }

    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->campaigns->show(
            $this->currentCompany->fromRequest($request),
            $campaign,
        ));
    }

    public function update(UpdateCompanyCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->campaigns->update(
            $this->currentCompany->fromRequest($request),
            $campaign,
            $request->validated(),
        ));
    }

    public function launch(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->campaigns->launch(
            $this->currentCompany->fromRequest($request),
            $campaign,
        ));
    }

    public function pause(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->campaigns->pause(
            $this->currentCompany->fromRequest($request),
            $campaign,
        ));
    }

    public function resume(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->campaigns->resume(
            $this->currentCompany->fromRequest($request),
            $campaign,
        ));
    }

    public function reopen(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->campaigns->reopen(
            $this->currentCompany->fromRequest($request),
            $campaign,
        ));
    }

    public function complete(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->campaigns->complete(
            $this->currentCompany->fromRequest($request),
            $campaign,
        ));
    }

    public function cancel(Request $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->campaigns->cancel(
            $this->currentCompany->fromRequest($request),
            $campaign,
        ));
    }

    private function actor(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
