<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;

class ChatCompanyCampaignBriefRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Company) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
            'brief' => ['sometimes', 'nullable', 'array'],
            'brief.context' => ['sometimes', 'nullable', 'string'],
            'brief.product' => ['sometimes', 'nullable', 'string'],
            'brief.differentiators' => ['sometimes', 'nullable', 'array'],
            'brief.differentiators.*' => ['nullable', 'string'],
            'brief.target' => ['sometimes', 'nullable', 'string'],
            'brief.pains' => ['sometimes', 'nullable', 'array'],
            'brief.pains.*' => ['nullable', 'string'],
            'brief.trigger' => ['sometimes', 'nullable', 'string'],
            'brief.key_message' => ['sometimes', 'nullable', 'string'],
            'brief.audience' => ['sometimes', 'nullable', 'array'],
            'brief.audience.industries' => ['sometimes', 'nullable', 'string'],
            'brief.audience.geographies' => ['sometimes', 'nullable', 'string'],
            'brief.audience.tone' => ['sometimes', 'nullable', 'string'],
            'brief.editorial' => ['sometimes', 'nullable', 'array'],
            'brief.editorial.do' => ['sometimes', 'nullable', 'array'],
            'brief.editorial.do.*' => ['nullable', 'string'],
            'brief.editorial.avoid' => ['sometimes', 'nullable', 'array'],
            'brief.editorial.avoid.*' => ['nullable', 'string'],
            'brief.references' => ['sometimes', 'nullable', 'array'],
            'brief.references.*.quote' => ['nullable', 'string'],
            'brief.references.*.structure' => ['nullable', 'string'],
            'brief.angles' => ['sometimes', 'nullable', 'array'],
            'brief.angles.*.title' => ['nullable', 'string'],
            'brief.angles.*.hook' => ['nullable', 'string'],
            'brief.angles.*.format' => ['nullable', 'string'],
            'brief.angles.*.example' => ['nullable', 'string'],
        ];
    }
}
