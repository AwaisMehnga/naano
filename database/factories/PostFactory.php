<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Collaboration;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collaboration_id' => Collaboration::factory(),
            'status' => PostStatus::Draft,
            'body' => fake()->paragraph(),
        ];
    }
}
