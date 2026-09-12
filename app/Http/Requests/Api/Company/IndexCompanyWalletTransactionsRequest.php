<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCompanyWalletTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('company') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['campaign_id', 'type', 'status'] as $key) {
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
            'campaign_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'type' => ['sometimes', 'nullable', Rule::enum(WalletTransactionType::class)],
            'status' => ['sometimes', 'nullable', Rule::enum(WalletTransactionStatus::class)],
        ];
    }
}
