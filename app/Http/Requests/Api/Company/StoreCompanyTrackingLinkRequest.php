<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyTrackingLinkRequest extends FormRequest
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
            'collaboration_id' => ['required', 'integer'],
            'destination_url' => ['required', 'url'],
            'post_id' => ['nullable', 'integer'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
        ];
    }
}
