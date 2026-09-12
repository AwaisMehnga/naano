<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\CollaborationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCompanyAllCollaborationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('company') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('status') === '') {
            $this->merge(['status' => null]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(CollaborationStatus::class)],
        ];
    }
}
