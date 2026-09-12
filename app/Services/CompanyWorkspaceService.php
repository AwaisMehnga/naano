<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyAccess;
use Illuminate\Http\Request;

class CompanyWorkspaceService
{
    public function __construct(private CurrentCompanyService $currentCompany) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function index(User $user, Request $request): array
    {
        $current = $this->currentCompany->resolve($request);

        return $user->companies()
            ->orderBy('companies.id')
            ->get()
            ->map(function (Company $company) use ($user, $current): array {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'role' => CompanyAccess::role($user, $company)?->value,
                    'is_current' => $current?->id === $company->id,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function switch(User $user, Company $company, Request $request): array
    {
        $role = CompanyAccess::role($user, $company);

        if ($role === null) {
            abort(404);
        }

        $request->session()->put('current_company_id', $company->id);
        $request->attributes->set('currentCompany', $company);

        return [
            'id' => $company->id,
            'name' => $company->name,
            'role' => $role->value,
            'is_current' => true,
        ];
    }
}
