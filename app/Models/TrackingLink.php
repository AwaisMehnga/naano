<?php

namespace App\Models;

use Database\Factories\TrackingLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $post_id
 * @property int $collaboration_id
 * @property string $destination_url
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_content
 * @property string|null $slug
 */
#[Fillable([
    'post_id',
    'collaboration_id',
    'destination_url',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_content',
    'slug',
])]
class TrackingLink extends Model
{
    /** @use HasFactory<TrackingLinkFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<Collaboration, $this>
     */
    public function collaboration(): BelongsTo
    {
        return $this->belongsTo(Collaboration::class);
    }

    /**
     * @return HasMany<TrackingClick, $this>
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(TrackingClick::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
