<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrackingEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:qualify,lead'],
            'payload' => ['nullable', 'array'],
            'visitor_key' => ['nullable', 'uuid'],
        ];
    }
}
