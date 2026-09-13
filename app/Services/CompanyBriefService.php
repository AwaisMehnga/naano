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

    public function step(Company $company, ?string $requested = null): string
    {
        if ($company->website === null || $requested === 'website') {
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

        if ($text === '') {
            return ['analyzed' => false];
        }

        try {
            $response = (new BrandBriefAgent)->prompt(
                implode("\n\n", [
                    'Write the brief from this page text only. Do not use outside knowledge.',
                    'Website: '.$website,
                    'Page text:',
                    $text,
                ]),
                provider: Lab::DeepSeek,
                timeout: 60,
            );

            $brief = $response instanceof StructuredAgentResponse ? $response->toArray() : [];

            $company->update([
                'value_proposition' => (string) ($brief['value_proposition'] ?? ''),
                'icps' => $this->icpsFromBrief($brief),
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

    /**
     * @param  array<string, mixed>  $brief
     * @return list<array{title: string, description: string}>
     */
    private function icpsFromBrief(array $brief): array
    {
        return collect($brief['icps'] ?? [])
            ->filter(fn (mixed $icp): bool => is_array($icp))
            ->map(fn (array $icp): array => [
                'title' => (string) ($icp['title'] ?? ''),
                'description' => (string) ($icp['description'] ?? ''),
            ])
            ->filter(fn (array $icp): bool => $icp['title'] !== '' && $icp['description'] !== '')
            ->take(5)
            ->values()
            ->all();
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
