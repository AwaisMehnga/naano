<?php

namespace App\Http\Requests\Api\Creator;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleCreatorPostRequest extends FormRequest
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
            'scheduled_at' => ['required', 'date'],
        ];
    }
}
