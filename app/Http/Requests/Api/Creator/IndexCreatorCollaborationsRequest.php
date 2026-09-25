<?php

namespace App\Http\Requests\Api\Creator;

use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCreatorCollaborationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Creator) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['status', 'q', 'source'] as $key) {
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
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'source' => ['sometimes', 'nullable', Rule::enum(CollaborationSource::class)],
        ];
    }
}
