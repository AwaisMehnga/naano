<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCompanyCreatorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Company) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['q', 'niche_id', 'country', 'min_price_cents', 'max_price_cents', 'min_followers', 'max_followers'] as $key) {
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
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'niche_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'country' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(config('onboarding.countries')))],
            'min_price_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_price_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'min_followers' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_followers' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
