<?php

namespace App\Services;

use App\Ai\Agents\CampaignBriefAgent;
use App\Ai\BriefDocument;
use App\Models\Campaign;
use App\Models\Company;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

class CampaignBriefChatService
{
    public function __construct(private CompanyBriefService $companyBrief) {}

    /**
     * @param  array<string, mixed>|null  $draftBrief
     * @return array{reply: string, brief: array<string, mixed>, patches: list<array{path: string, before: string, after: string}>}
     */
    public function chat(
        Company $company,
        Campaign $campaign,
        string $message,
        ?array $draftBrief = null,
    ): array {
        if ($campaign->company_id !== $company->id) {
            abort(404);
        }

        $industries = array_values(array_filter(
            config('onboarding.industries', []),
            fn ($value): bool => is_string($value) && $value !== '',
        ));
        $regions = array_values(array_filter(
            config('audience.regions', []),
            fn ($value): bool => is_string($value) && $value !== '',
        ));
        $tones = array_values(array_filter(
            config('audience.tones', []),
            fn ($value): bool => is_string($value) && $value !== '',
        ));

        $seed = is_array($draftBrief)
            ? $draftBrief
            : (is_array($campaign->brief) ? $campaign->brief : []);
        $document = new BriefDocument($seed);

        $siteText = '';

        if (is_string($company->website) && $company->website !== '') {
            try {
                $siteText = $this->companyBrief->fetchSiteText($company->website);
            } catch (Throwable) {
                $siteText = '';
            }
        }

        $prompt = implode("\n\n", array_filter([
            'Company name: '.$company->name,
            'Website: '.($company->website ?? 'none'),
            'Value proposition: '.($company->value_proposition ?? 'none'),
            'ICPs: '.json_encode($company->icps ?? [], JSON_UNESCAPED_UNICODE),
            $siteText !== '' ? "Website page text:\n".$siteText : null,
            'Campaign metadata: '.json_encode([
                'name' => $campaign->name,
                'type' => $campaign->type->value,
                'objective' => $campaign->objective->value,
                'status' => $campaign->status->value,
            ], JSON_UNESCAPED_UNICODE),
            'The current brief is available via read_brief. Edit only what the user asked for.',
            'User message: '.$message,
        ]));

        $response = (new CampaignBriefAgent($document, $industries, $regions, $tones))->prompt(
            $prompt,
            provider: Lab::DeepSeek,
            timeout: 90,
        );

        $reply = $response instanceof AgentResponse
            ? trim($response->text)
            : '';

        return [
            'reply' => $reply !== '' ? $reply : 'Done. Review the form and save when ready.',
            'brief' => $document->snapshot(),
            'patches' => $document->patches(),
        ];
    }
}
