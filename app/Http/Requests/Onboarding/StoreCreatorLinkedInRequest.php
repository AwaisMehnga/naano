<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCreatorLinkedInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('creator') ?? false;
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'linkedin_url' => ['required', 'url', 'max:255', 'regex:/^https?:\/\/(www\.)?linkedin\.com\/in\/.+/i'],
            'headline' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', Rule::in(array_keys(config('onboarding.countries')))],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
