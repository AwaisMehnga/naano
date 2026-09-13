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
        foreach (['status', 'pipeline', 'q', 'campaign_id'] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(CollaborationStatus::class)],
            'pipeline' => ['sometimes', 'nullable', 'string', Rule::in([
                'all',
                'active',
                'invitations_received',
                'invitations_sent',
                'todo',
                'completed',
            ])],
            'campaign_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
