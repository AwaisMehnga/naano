<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyIcp;

class CompanyIcpService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function index(Company $company): array
    {
        $this->seedFromOnboarding($company);

        return $company->companyIcps()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (CompanyIcp $icp): array => $this->payload($icp))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(Company $company, array $data): array
    {
        $max = (int) $company->companyIcps()->max('sort_order');

        $icp = $company->companyIcps()->create([
            'title' => $data['title'],
            'description' => $data['description'],
            'sort_order' => $data['sort_order'] ?? ($max + 1),
            'tags' => $data['tags'] ?? [],
            'industries' => $data['industries'] ?? [],
            'regions' => $data['regions'] ?? [],
        ]);

        return $this->payload($icp);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(Company $company, CompanyIcp $icp, array $data): array
    {
        $this->ensureOwned($company, $icp);

        $icp->fill($data);
        $icp->save();

        return $this->payload($icp);
    }

    public function destroy(Company $company, CompanyIcp $icp): void
    {
        $this->ensureOwned($company, $icp);

        $icp->delete();
    }

    private function seedFromOnboarding(Company $company): void
    {
        if ($company->companyIcps()->exists()) {
            return;
        }

        foreach ($company->icps ?? [] as $index => $icp) {
            if (! is_array($icp) || ! isset($icp['title'], $icp['description'])) {
                continue;
            }

            $company->companyIcps()->create([
                'title' => $icp['title'],
                'description' => $icp['description'],
                'sort_order' => $index,
                'tags' => [],
                'industries' => [],
                'regions' => [],
            ]);
        }
    }

    private function ensureOwned(Company $company, CompanyIcp $icp): void
    {
        if ($icp->company_id !== $company->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CompanyIcp $icp): array
    {
        return [
            'id' => $icp->id,
            'title' => $icp->title,
            'description' => $icp->description,
            'sort_order' => $icp->sort_order,
            'tags' => $icp->tags ?? [],
            'industries' => $icp->industries ?? [],
            'regions' => $icp->regions ?? [],
        ];
    }
}
