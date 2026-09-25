<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\CollaborationStatus;
use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCompanyCollaborationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Company) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['pipeline', 'status'] as $key) {
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
            'pipeline' => ['sometimes', 'nullable', 'string', Rule::in([
                'all',
                'active',
                'invitations_received',
                'invitations_sent',
                'todo',
                'completed',
            ])],
            'status' => ['sometimes', 'nullable', Rule::enum(CollaborationStatus::class)],
        ];
    }
}
