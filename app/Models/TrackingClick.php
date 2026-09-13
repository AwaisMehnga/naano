<?php

namespace App\Models;

use Database\Factories\TrackingClickFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tracking_link_id
 * @property int|null $post_id
 * @property string $visitor_key
 * @property string|null $ip_hash
 * @property string|null $user_agent
 * @property Carbon $occurred_at
 */
#[Fillable([
    'tracking_link_id',
    'post_id',
    'visitor_key',
    'ip_hash',
    'user_agent',
    'occurred_at',
])]
class TrackingClick extends Model
{
    /** @use HasFactory<TrackingClickFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TrackingLink, $this>
     */
    public function trackingLink(): BelongsTo
    {
        return $this->belongsTo(TrackingLink::class);
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
