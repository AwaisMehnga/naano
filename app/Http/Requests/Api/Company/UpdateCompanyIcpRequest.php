<?php

namespace App\Http\Requests\Api\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyIcpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('company') ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:120'],
            'description' => ['sometimes', 'string', 'max:800'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:80'],
            'industries' => ['sometimes', 'array'],
            'industries.*' => ['string', Rule::in(config('onboarding.industries'))],
            'regions' => ['sometimes', 'array'],
            'regions.*' => ['string', Rule::in(config('audience.regions'))],
        ];
    }
}
