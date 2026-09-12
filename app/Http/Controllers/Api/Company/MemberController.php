<?php

namespace App\Http\Controllers\Api\Company;

use App\Enums\CompanyMemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\StoreCompanyMemberRequest;
use App\Http\Requests\Api\Company\UpdateCompanyMemberRequest;
use App\Models\CompanyMember;
use App\Models\User;
use App\Services\CompanyMemberService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(
        private CompanyMemberService $members,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->members->index($this->currentCompany->fromRequest($request)));
    }

    public function store(StoreCompanyMemberRequest $request): JsonResponse
    {
        $role = $request->enum('role', CompanyMemberRole::class) ?? CompanyMemberRole::Member;

        return AjaxResponse::success($this->members->invite(
            $this->actor($request),
            $this->currentCompany->fromRequest($request),
            $request->validated('email'),
            $role,
        ));
    }

    public function update(UpdateCompanyMemberRequest $request, CompanyMember $member): JsonResponse
    {
        $role = $request->enum('role', CompanyMemberRole::class);

        if (! $role instanceof CompanyMemberRole) {
            abort(422, 'The role field is required.');
        }

        return AjaxResponse::success($this->members->updateRole(
            $this->actor($request),
            $this->currentCompany->fromRequest($request),
            $member,
            $role,
        ));
    }

    public function destroy(Request $request, CompanyMember $member): JsonResponse
    {
        $this->members->destroy(
            $this->actor($request),
            $this->currentCompany->fromRequest($request),
            $member,
        );

        return AjaxResponse::success([]);
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
