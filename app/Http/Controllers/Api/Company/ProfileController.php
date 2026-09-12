<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\UpdateCompanyProfileRequest;
use App\Models\User;
use App\Services\CompanyProfileService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private CompanyProfileService $profiles,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->profiles->show(
            $this->actor($request),
            $this->currentCompany->fromRequest($request),
        ));
    }

    public function update(UpdateCompanyProfileRequest $request): JsonResponse
    {
        $data = $request->safe()->only([
            'name',
            'website',
            'billing_email',
            'country',
            'value_proposition',
        ]);

        return AjaxResponse::success($this->profiles->update(
            $this->actor($request),
            $this->currentCompany->fromRequest($request),
            $data,
            $request->file('logo'),
            $request->boolean('remove_logo'),
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
