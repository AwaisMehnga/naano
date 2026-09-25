<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\CampaignObjective;
use App\Enums\CampaignType;
use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Company) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['company_icp_id', 'budget_cents', 'goal', 'guidelines', 'start_at', 'end_at'] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'type' => ['sometimes', Rule::enum(CampaignType::class)],
            'objective' => ['sometimes', Rule::enum(CampaignObjective::class)],
            'budget_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'company_icp_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'goal' => ['sometimes', 'nullable', 'string'],
            'key_messages' => ['sometimes', 'nullable', 'array'],
            'key_messages.*' => ['string', 'max:500'],
            'guidelines' => ['sometimes', 'nullable', 'string'],
            'start_at' => ['sometimes', 'nullable', 'date'],
            'end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_at'],
            'brief' => ['sometimes', 'nullable', 'array'],
            'brief.context' => ['sometimes', 'nullable', 'string'],
            'brief.product' => ['sometimes', 'nullable', 'string'],
            'brief.differentiators' => ['sometimes', 'array'],
            'brief.differentiators.*' => ['string', 'max:500'],
            'brief.target' => ['sometimes', 'nullable', 'string'],
            'brief.pains' => ['sometimes', 'array'],
            'brief.pains.*' => ['string', 'max:500'],
            'brief.trigger' => ['sometimes', 'nullable', 'string'],
            'brief.key_message' => ['sometimes', 'nullable', 'string', 'max:500'],
            'brief.audience' => ['sometimes', 'array'],
            'brief.audience.industries' => ['sometimes', 'nullable', 'string', 'max:255'],
            'brief.audience.geographies' => ['sometimes', 'nullable', 'string', 'max:255'],
            'brief.audience.tone' => ['sometimes', 'nullable', 'string'],
            'brief.editorial' => ['sometimes', 'array'],
            'brief.editorial.do' => ['sometimes', 'array'],
            'brief.editorial.do.*' => ['string', 'max:500'],
            'brief.editorial.avoid' => ['sometimes', 'array'],
            'brief.editorial.avoid.*' => ['string', 'max:500'],
            'brief.references' => ['sometimes', 'array'],
            'brief.references.*.quote' => ['sometimes', 'nullable', 'string'],
            'brief.references.*.structure' => ['sometimes', 'nullable', 'string'],
            'brief.angles' => ['sometimes', 'array'],
            'brief.angles.*.title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'brief.angles.*.hook' => ['sometimes', 'nullable', 'string'],
            'brief.angles.*.format' => ['sometimes', 'nullable', 'string'],
            'brief.angles.*.example' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
