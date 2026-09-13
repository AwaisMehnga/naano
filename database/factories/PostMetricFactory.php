<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostMetric>
 */
class PostMetricFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'impressions' => 0,
            'likes' => 0,
            'comments' => 0,
            'clicks' => 0,
            'unique_clicks' => 0,
            'qualified_clicks' => 0,
            'leads_count' => 0,
            'captured_at' => now(),
        ];
    }
}
