<?php

namespace App\Http\Requests\Api\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyPostChangesRequest extends FormRequest
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
            'review_note' => ['required', 'string'],
        ];
    }
}
