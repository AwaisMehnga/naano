<?php

namespace App\Models;

use Database\Factories\TrackingEventFactory;
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
 * @property string $type
 * @property array<string, mixed>|null $payload
 * @property Carbon $occurred_at
 */
#[Fillable([
    'tracking_link_id',
    'post_id',
    'visitor_key',
    'type',
    'payload',
    'occurred_at',
])]
class TrackingEvent extends Model
{
    /** @use HasFactory<TrackingEventFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
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
