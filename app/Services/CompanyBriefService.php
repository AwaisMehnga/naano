<?php

namespace App\Services;

use App\Ai\Agents\BrandBriefAgent;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class CompanyBriefService
{
    public function company(User $user): Company
    {
        return $user->company()->firstOrCreate([]);
    }

    public function step(Company $company): string
    {
        if ($company->website === null) {
            return 'website';
        }

        return 'brief';
    }

    /**
     * @return array{analyzed: bool}
     */
    public function analyze(Company $company, string $website): array
    {
        $company->update(['website' => $website]);

        $text = $this->fetchSiteText($website);

        try {
            $response = (new BrandBriefAgent)->prompt(
                $text === '' ? 'Website: '.$website : $text,
                provider: Lab::DeepSeek,
                timeout: 60,
            );

            $brief = $response instanceof StructuredAgentResponse ? $response->toArray() : [];

            $company->update([
                'value_proposition' => (string) ($brief['value_proposition'] ?? ''),
                'icps' => [
                    ['title' => (string) ($brief['icp_1_title'] ?? ''), 'description' => (string) ($brief['icp_1_description'] ?? '')],
                    ['title' => (string) ($brief['icp_2_title'] ?? ''), 'description' => (string) ($brief['icp_2_description'] ?? '')],
                    ['title' => (string) ($brief['icp_3_title'] ?? ''), 'description' => (string) ($brief['icp_3_description'] ?? '')],
                ],
            ]);

            return ['analyzed' => true];
        } catch (Throwable) {
            return ['analyzed' => false];
        }
    }

    /**
     * @param  array{value_proposition: string, icps: list<array{title: string, description: string}>}  $data
     */
    public function saveBrief(Company $company, array $data): void
    {
        $company->update([
            'value_proposition' => $data['value_proposition'],
            'icps' => $data['icps'],
            'onboarded_at' => now(),
        ]);
    }

    private function fetchSiteText(string $website): string
    {
        try {
            $html = Http::connectTimeout(5)
                ->timeout(20)
                ->get($website)
                ->throw()
                ->body();
        } catch (Throwable) {
            return '';
        }

        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');

        return Str::limit($text, 8000, '');
    }
}
