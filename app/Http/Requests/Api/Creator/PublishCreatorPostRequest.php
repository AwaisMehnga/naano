<?php

namespace App\Http\Requests\Api\Creator;

use Illuminate\Foundation\Http\FormRequest;

class PublishCreatorPostRequest extends FormRequest
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
            'published_url' => ['required', 'url'],
            'linkedin_post_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
