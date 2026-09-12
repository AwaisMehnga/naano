<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\CollaborationEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyCollaborationFollowUpRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in([
                CollaborationEventType::FollowUp->value,
                CollaborationEventType::Note->value,
            ])],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
