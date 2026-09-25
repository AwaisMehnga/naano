<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Company) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'source' => ['nullable', 'in:manual'],
            'post_id' => ['nullable', 'integer'],
            'tracking_link_id' => ['nullable', 'integer'],
            'payload' => ['nullable', 'array'],
            'payload.pipeline_cents' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
