<?php

namespace App\Http\Requests\Api\Creator;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreatorCollaborationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('creator') ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
