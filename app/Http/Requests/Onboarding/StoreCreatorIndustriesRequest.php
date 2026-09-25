<?php

namespace App\Http\Requests\Onboarding;

use App\Enums\ProfileType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCreatorIndustriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Creator) ?? false;
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'industries' => ['required', 'array', 'min:1', 'max:3'],
            'industries.*' => ['required', 'string', Rule::in(config('onboarding.industries'))],
        ];
    }
}
