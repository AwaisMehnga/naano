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

        return $user->company;
    }

    public function fromRequest(Request $request): Company
    {
        $company = $request->attributes->get('currentCompany');

        if ($company instanceof Company) {
            return $company;
        }

        $resolved = $this->resolve($request);

        if (! $resolved instanceof Company) {
            abort(403, 'No company profile.');
        }

        return $resolved;
    }
}
