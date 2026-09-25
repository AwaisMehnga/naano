<?php

namespace App\Http\Requests\Api\Creator;

use App\Enums\CampaignObjective;
use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCreatorOpportunitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Creator) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['q', 'objective', 'country', 'match', 'sort', 'limit'] as $key) {
            if ($this->input($key) === '' || $this->input($key) === 'all') {
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
            'objective' => ['sometimes', 'nullable', Rule::enum(CampaignObjective::class)],
            'country' => ['sometimes', 'nullable', 'string', 'max:8'],
            'match' => ['sometimes', 'nullable', 'integer', Rule::in([50, 70])],
            'sort' => ['sometimes', 'nullable', Rule::in(['match', 'deadline', 'name'])],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
