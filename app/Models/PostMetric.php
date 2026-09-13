<?php

namespace App\Models;

use Database\Factories\PostMetricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $post_id
 * @property int $impressions
 * @property int $likes
 * @property int $comments
 * @property int $clicks
 * @property int $unique_clicks
 * @property int $qualified_clicks
 * @property int $leads_count
 * @property int|null $cpm_cents
 * @property Carbon|null $captured_at
 */
#[Fillable([
    'post_id',
    'impressions',
    'likes',
    'comments',
    'clicks',
    'unique_clicks',
    'qualified_clicks',
    'leads_count',
    'cpm_cents',
    'captured_at',
])]
class PostMetric extends Model
{
    /** @use HasFactory<PostMetricFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
