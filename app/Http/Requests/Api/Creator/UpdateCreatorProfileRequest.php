<?php

namespace App\Http\Requests\Api\Creator;

use App\Enums\ProfileType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCreatorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Creator) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['linkedin_url', 'display_name', 'headline', 'bio', 'country'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'linkedin_url' => ['sometimes', 'nullable', 'url', 'max:255', 'regex:/^https?:\/\/(www\.)?linkedin\.com\/in\/.+/i'],
            'headline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'country' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(config('onboarding.countries')))],
            'photo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['sometimes', 'boolean'],
        ];
    }
}
