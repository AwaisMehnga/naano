<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'email_invites' => ['required', 'boolean'],
            'email_applications' => ['required', 'boolean'],
            'email_campaign_updates' => ['required', 'boolean'],
        ];
    }
}
