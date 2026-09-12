<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\BookCompanyCollaborationRequest;
use App\Http\Requests\Api\Company\StoreCompanyCollaborationFollowUpRequest;
use App\Models\Collaboration;
use App\Models\User;
use App\Services\CompanyCollaborationService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollaborationActionController extends Controller
{
    public function __construct(
        private CompanyCollaborationService $collaborations,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function select(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->select(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $collaboration,
        ));
    }

    public function book(BookCompanyCollaborationRequest $request, Collaboration $collaboration): JsonResponse
    {
        $offerId = $request->validated('creator_offer_id');

        return AjaxResponse::success($this->collaborations->book(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $collaboration,
            $offerId === null ? null : (int) $offerId,
        ));
    }

    public function cancel(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->cancel(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $collaboration,
        ));
    }

    public function followUp(StoreCompanyCollaborationFollowUpRequest $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->collaborations->followUp(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $collaboration,
            (string) $request->validated('type'),
            (string) $request->validated('body'),
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
