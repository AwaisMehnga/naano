<?php

namespace App\Models;

use App\Enums\PostStatus;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $collaboration_id
 * @property PostStatus $status
 * @property string|null $body
 * @property string|null $review_note
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $published_at
 * @property string|null $published_url
 * @property string|null $linkedin_post_id
 * @property Carbon|null $submitted_at
 * @property Carbon|null $reviewed_at
 * @property int|null $reviewed_by_user_id
 */
#[Fillable([
    'collaboration_id',
    'status',
    'body',
    'review_note',
    'scheduled_at',
    'published_at',
    'published_url',
    'linkedin_post_id',
    'submitted_at',
    'reviewed_at',
    'reviewed_by_user_id',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Collaboration, $this>
     */
    public function collaboration(): BelongsTo
    {
        return $this->belongsTo(Collaboration::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * @return HasMany<TrackingLink, $this>
     */
    public function trackingLinks(): HasMany
    {
        return $this->hasMany(TrackingLink::class);
    }

    /**
     * @return HasOne<PostMetric, $this>
     */
    public function metrics(): HasOne
    {
        return $this->hasOne(PostMetric::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
