<?php

namespace App\Http\Requests\Api\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyLeadRequest extends FormRequest
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
            'payload' => ['required', 'array'],
            'payload.pipeline_cents' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
