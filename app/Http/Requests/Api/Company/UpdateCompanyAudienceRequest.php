<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyAudienceRequest extends FormRequest
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
            'industries' => ['sometimes', 'array'],
            'industries.*' => ['string', Rule::in(config('onboarding.industries'))],
            'regions' => ['sometimes', 'array'],
            'regions.*' => ['string', Rule::in(config('audience.regions'))],
            'titles' => ['sometimes', 'array'],
            'titles.*' => ['string', 'max:120'],
            'seniority' => ['sometimes', 'array'],
            'seniority.*' => ['string', Rule::in(config('audience.seniority'))],
            'company_sizes' => ['sometimes', 'array'],
            'company_sizes.*' => ['string', Rule::in(config('audience.company_sizes'))],
        ];
    }
}
