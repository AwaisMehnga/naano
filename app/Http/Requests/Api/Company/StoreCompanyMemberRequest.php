<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\CompanyMemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyMemberRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'role' => ['sometimes', Rule::enum(CompanyMemberRole::class)],
        ];
    }
}
