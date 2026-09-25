<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\ProfileType;
use Illuminate\Foundation\Http\FormRequest;

class InviteCompanyCampaignCreatorRequest extends FormRequest
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
            'creator_profile_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
