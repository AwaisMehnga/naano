<?php

namespace App\Services;

use App\Enums\LeadSource;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Lead;

class CompanyLeadService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function index(Company $company, Campaign $campaign): array
    {
        $this->ensureCampaignOwned($company, $campaign);

        return array_values($campaign->leads()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Lead $lead): array => $this->payload($lead))
            ->all());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(Company $company, Campaign $campaign, array $data): array
    {
        $this->ensureCampaignOwned($company, $campaign);

        $lead = Lead::query()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'post_id' => $data['post_id'] ?? null,
            'tracking_link_id' => $data['tracking_link_id'] ?? null,
            'occurred_at' => now(),
            'source' => LeadSource::Manual,
            'payload' => is_array($data['payload'] ?? null) ? $data['payload'] : null,
        ]);

        return $this->payload($lead);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(Company $company, Lead $lead, array $data): array
    {
        $this->ensureLeadOwned($company, $lead);

        $current = is_array($lead->payload) ? $lead->payload : [];
        $incoming = is_array($data['payload'] ?? null) ? $data['payload'] : [];
        $lead->payload = [...$current, ...$incoming];
        $lead->save();

        return $this->payload($lead->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'company_id' => $lead->company_id,
            'campaign_id' => $lead->campaign_id,
            'post_id' => $lead->post_id,
            'tracking_link_id' => $lead->tracking_link_id,
            'occurred_at' => $lead->occurred_at?->toIso8601String(),
            'source' => $lead->source->value,
            'payload' => $lead->payload,
        ];
    }

    private function ensureCampaignOwned(Company $company, Campaign $campaign): void
    {
        if ($campaign->company_id !== $company->id) {
            abort(404);
        }
    }

    private function ensureLeadOwned(Company $company, Lead $lead): void
    {
        if ($lead->company_id !== $company->id) {
            abort(404);
        }
    }
}
