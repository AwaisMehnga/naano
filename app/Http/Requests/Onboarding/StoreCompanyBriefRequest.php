<?php

namespace App\Http\Requests\Onboarding;

use App\Enums\ProfileType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyBriefRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Company) ?? false;
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'value_proposition' => ['required', 'string', 'min:40', 'max:4000'],
            'icps' => ['required', 'array', 'min:1', 'max:5'],
            'icps.*.title' => ['required', 'string', 'max:120'],
            'icps.*.description' => ['required', 'string', 'max:800'],
        ];
    }
}
