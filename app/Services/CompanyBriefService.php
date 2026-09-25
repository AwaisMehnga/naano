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
     * @return array{analyzed: bool, empty_page: bool}
     */
    public function analyze(Company $company, string $website): array
    {
        $company->update(['website' => $website]);

        $text = $this->fetchSiteText($website);

        if ($text === '') {
            return ['analyzed' => false, 'empty_page' => true];
        }

        try {
            $response = (new BrandBriefAgent)->prompt(
                implode("\n\n", [
                    'Write a B2B marketplace brief from this page text only. Do not use outside knowledge.',
                    'Website: '.$website,
                    'Page text:',
                    $text,
                ]),
                provider: Lab::DeepSeek,
                timeout: 90,
            );

            $brief = $response instanceof StructuredAgentResponse ? $response->toArray() : [];
            $valueProposition = trim((string) ($brief['value_proposition'] ?? ''));
            $icps = $this->icpsFromBrief($brief);

            if ($valueProposition === '' && $icps === []) {
                return ['analyzed' => false, 'empty_page' => false];
            }

            $company->update([
                'value_proposition' => $valueProposition !== '' ? $valueProposition : null,
                'icps' => $icps !== [] ? $icps : null,
            ]);

            return ['analyzed' => true, 'empty_page' => false];
        } catch (Throwable) {
            return ['analyzed' => false, 'empty_page' => false];
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

    public function fetchSiteText(string $website): string
    {
        try {
            $html = Http::connectTimeout(5)
                ->timeout(20)
                ->withHeaders([
                    'User-Agent' => 'NaanoBriefBot/1.0 (+https://naano.app)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($website)
                ->throw()
                ->body();
        } catch (Throwable) {
            return '';
        }

        $parts = [];

        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match) === 1) {
            $parts[] = 'Title: '.$this->cleanText($match[1]);
        }

        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/is', $html, $match) === 1
            || preg_match('/<meta[^>]+content=["\'](.*?)["\'][^>]+name=["\']description["\']/is', $html, $match) === 1) {
            $parts[] = 'Description: '.$this->cleanText(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5));
        }

        if (preg_match('/<main\b[^>]*>(.*?)<\/main>/is', $html, $match) === 1) {
            $parts[] = $this->cleanText(strip_tags($match[1]));
        } elseif (preg_match('/<article\b[^>]*>(.*?)<\/article>/is', $html, $match) === 1) {
            $parts[] = $this->cleanText(strip_tags($match[1]));
        } else {
            $body = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
            $body = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $body) ?? $body;
            $parts[] = $this->cleanText(strip_tags($body));
        }

        $text = trim(implode("\n\n", array_filter($parts)));

        return Str::limit($text, 12000, '');
    }

    private function cleanText(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
