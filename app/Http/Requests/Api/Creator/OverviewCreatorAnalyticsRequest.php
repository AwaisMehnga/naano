<?php

namespace App\Http\Requests\Api\Creator;

use App\Enums\ProfileType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OverviewCreatorAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ownsProfile(ProfileType::Creator) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['from', 'to'] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'nullable', 'date', 'required_with:to'],
            'to' => ['sometimes', 'nullable', 'date', 'required_with:from', 'after_or_equal:from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $from = $this->date('from');
            $to = $this->date('to');

            if ($from !== null && $to !== null && $from->diffInDays($to) > 90) {
                $validator->errors()->add('to', 'The range may not exceed 90 days.');
            }
        });
    }
}
