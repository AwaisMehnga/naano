<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\BookCompanyCampaignCreatorRequest;
use App\Http\Requests\Api\Company\IndexCompanyCollaborationsRequest;
use App\Http\Requests\Api\Company\InviteCompanyCampaignCreatorRequest;
use App\Models\Campaign;
use App\Models\User;
use App\Services\CompanyCollaborationService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignCollaborationController extends Controller
{
    public function __construct(
        private CompanyCollaborationService $collaborations,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function index(IndexCompanyCollaborationsRequest $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->index(
            $this->currentCompany->fromRequest($request),
            $campaign,
            $request->validated(),
        ));
    }

    public function store(InviteCompanyCampaignCreatorRequest $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->invite(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $campaign,
            (int) $request->validated('creator_profile_id'),
        ));
    }

    public function source(InviteCompanyCampaignCreatorRequest $request, Campaign $campaign): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->source(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $campaign,
            (int) $request->validated('creator_profile_id'),
        ));
    }

    public function book(BookCompanyCampaignCreatorRequest $request, Campaign $campaign): JsonResponse
    {
        $offerId = $request->validated('creator_offer_id');

        return AjaxResponse::success($this->collaborations->bookListed(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $campaign,
            (int) $request->validated('creator_profile_id'),
            $offerId === null ? null : (int) $offerId,
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
