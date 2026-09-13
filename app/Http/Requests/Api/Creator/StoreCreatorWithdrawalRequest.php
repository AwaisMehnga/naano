<?php

namespace App\Http\Requests\Api\Creator;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreatorWithdrawalRequest extends FormRequest
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
            'amount_cents' => ['required', 'integer', 'min:1'],
        ];
    }
}
