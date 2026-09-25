<?php

namespace App\Services;

use App\Models\Company;

class CompanyAudienceService
{
    /**
     * @return array<string, mixed>
     */
    public function show(Company $company): array
    {
        return [
            'targeting' => $this->targeting($company),
            'lookups' => [
                'industries' => config('onboarding.industries'),
                'regions' => config('audience.regions'),
                'seniority' => config('audience.seniority'),
                'company_sizes' => config('audience.company_sizes'),
                'tones' => config('audience.tones'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(Company $company, array $data): array
    {
        $company->audience_targeting = [
            'industries' => $data['industries'] ?? [],
            'regions' => $data['regions'] ?? [],
            'titles' => $data['titles'] ?? [],
            'seniority' => $data['seniority'] ?? [],
            'company_sizes' => $data['company_sizes'] ?? [],
        ];
        $company->save();

        return $this->show($company->fresh() ?? $company);
    }

    /**
     * @return array{industries: list<string>, regions: list<string>, titles: list<string>, seniority: list<string>, company_sizes: list<string>}
     */
    private function targeting(Company $company): array
    {
        $targeting = $company->audience_targeting ?? [];

        return [
            'industries' => $targeting['industries'] ?? [],
            'regions' => $targeting['regions'] ?? [],
            'titles' => $targeting['titles'] ?? [],
            'seniority' => $targeting['seniority'] ?? [],
            'company_sizes' => $targeting['company_sizes'] ?? [],
        ];
    }
}
