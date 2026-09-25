<?php

namespace App\Http\Requests\Api\Creator;

use App\Enums\ProfileType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCreatorNichesRequest extends FormRequest
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
            'niche_ids' => ['required', 'array'],
            'niche_ids.*' => ['integer'],
        ];
    }
}
