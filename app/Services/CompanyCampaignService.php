<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Company;

class CompanyCampaignService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{id: int, name: string, status: string}>
     */
    public function index(Company $company, array $filters): array
    {
        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';

        $query = $company->campaigns()
            ->whereNot('status', CampaignStatus::Cancelled)
            ->orderByDesc('id')
            ->limit(20);

        if ($q !== '') {
            $query->where('name', 'like', '%'.$q.'%');
        }

        return $query->get()
            ->map(fn (Campaign $campaign): array => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status->value,
            ])
            ->all();
    }
}
