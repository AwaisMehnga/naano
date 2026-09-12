<?php

namespace App\Services;

use App\Enums\ContractStatus;
use App\Models\Collaboration;
use App\Models\Company;
use App\Models\Contract;

class ContractService
{
    /**
     * @return array<string, mixed>
     */
    public function generate(Company $company, Collaboration $collaboration): array
    {
        $collaboration->loadMissing(['campaign', 'creatorProfile']);

        $existing = $collaboration->contract;

        if ($existing instanceof Contract && $existing->status !== ContractStatus::Void) {
            return $this->payload($existing);
        }

        $contract = $collaboration->contract()->create([
            'status' => ContractStatus::Active,
            'generated_at' => now(),
            'terms' => $this->terms($company, $collaboration),
        ]);

        return $this->payload($contract);
    }

    public function void(Collaboration $collaboration): void
    {
        $contract = $collaboration->contract;

        if (! $contract instanceof Contract) {
            return;
        }

        $contract->status = ContractStatus::Void;
        $contract->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function show(Contract $contract): array
    {
        return $this->payload($contract);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Contract $contract): array
    {
        return [
            'id' => $contract->id,
            'status' => $contract->status->value,
            'generated_at' => $contract->generated_at?->toIso8601String(),
            'pdf_path' => $contract->pdf_path,
            'terms' => $contract->terms,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function terms(Company $company, Collaboration $collaboration): array
    {
        $profile = $collaboration->creatorProfile;
        $campaign = $collaboration->campaign;

        return [
            'brand' => [
                'name' => $company->name,
                'country' => $company->country,
            ],
            'creator' => [
                'display_name' => $profile->display_name,
                'country' => $profile->country,
            ],
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
            ],
            'offer' => [
                'posts_count' => $collaboration->booked_posts_count,
                'price_cents' => $collaboration->booked_price_cents,
                'currency' => 'EUR',
            ],
            'clauses' => [
                'The brand books the creator for the posts and price above.',
                'The platform is the counterparty. The brand does not pay the creator directly.',
                'Funds are held in the brand wallet until the live LinkedIn URL is submitted.',
                'The creator keeps 100% of the booked price.',
                'Draft approval grants publish permission only and does not move money.',
            ],
            'version' => 1,
        ];
    }
}
