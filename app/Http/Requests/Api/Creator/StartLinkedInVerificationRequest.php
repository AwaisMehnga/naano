<?php

namespace App\Http\Requests\Api\Creator;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StartLinkedInVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'linkedin_url' => ['required', 'url', 'max:255', 'regex:/^https?:\/\/(www\.)?linkedin\.com\/in\/.+/i'],
        ];
    }
}
