<?php

namespace App\Http\Requests\Onboarding;

use App\Enums\ProfileType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCreatorOfferRequest extends FormRequest
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
            'price' => ['required', 'numeric', 'min:1', 'max:10000'],
            'bundles' => ['nullable', 'array', 'max:5'],
            'bundles.*.posts' => ['required', 'integer', 'min:2', 'max:50'],
            'bundles.*.total' => ['required', 'numeric', 'min:1', 'max:500000'],
        ];
    }
}
