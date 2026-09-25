<?php

namespace App\Http\Requests\Api\Creator;

use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleCreatorPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Creator) ?? false;
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
