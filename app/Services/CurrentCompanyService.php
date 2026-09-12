<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class CurrentCompanyService
{
    public function resolve(Request $request): ?Company
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        $memberships = $user->companyMemberships()->orderBy('id')->get();

        if ($memberships->isEmpty()) {
            return null;
        }

        $headerId = $request->headers->get('X-Company-Id');

        if (is_string($headerId) && $headerId !== '') {
            $membership = $memberships->firstWhere('company_id', (int) $headerId);

            if ($membership === null) {
                return null;
            }

            $company = Company::query()->find($membership->company_id);

            if ($company instanceof Company) {
                $request->session()->put('current_company_id', $company->id);
            }

            return $company;
        }

        $sessionId = $request->session()->get('current_company_id');

        if (is_numeric($sessionId)) {
            $membership = $memberships->firstWhere('company_id', (int) $sessionId);

            if ($membership !== null) {
                return Company::query()->find($membership->company_id);
            }
        }

        $company = Company::query()->find($memberships->first()->company_id);

        if ($company instanceof Company) {
            $request->session()->put('current_company_id', $company->id);
        }

        return $company;
    }

    public function fromRequest(Request $request): Company
    {
        $company = $request->attributes->get('currentCompany');

        if (! $company instanceof Company) {
            abort(403, 'No company workspace.');
        }

        return $company;
    }
}
