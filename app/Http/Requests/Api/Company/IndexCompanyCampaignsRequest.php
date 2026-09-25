<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCompanyCampaignsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Company) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['q', 'status', 'type'] as $key) {
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
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'nullable', Rule::enum(CampaignStatus::class)],
            'type' => ['sometimes', 'nullable', Rule::enum(CampaignType::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
