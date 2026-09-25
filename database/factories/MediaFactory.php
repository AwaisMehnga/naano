<?php

namespace Database\Factories;

use App\Enums\MediaKind;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'disk' => 'public',
            'path' => 'media/demo/'.fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'kind' => MediaKind::Image->value,
            'size_bytes' => 1024,
            'original_name' => 'photo.jpg',
            'meta' => null,
        ];
    }
}
