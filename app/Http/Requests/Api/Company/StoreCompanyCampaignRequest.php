<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\CampaignObjective;
use App\Enums\CampaignType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('company') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['company_icp_id', 'budget_cents'] as $key) {
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
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(CampaignType::class)],
            'objective' => ['required', Rule::enum(CampaignObjective::class)],
            'budget_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'company_icp_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
