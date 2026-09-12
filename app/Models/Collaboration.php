<?php

namespace App\Models;

use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use Database\Factories\CollaborationFactory;
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
 * @property int $campaign_id
 * @property int $creator_profile_id
 * @property int|null $creator_offer_id
 * @property CollaborationSource $source
 * @property CollaborationStatus $status
 * @property int|null $booked_price_cents
 * @property int|null $booked_posts_count
 * @property int|null $invited_by_user_id
 * @property Carbon|null $accepted_at
 * @property Carbon|null $booked_at
 * @property Carbon|null $cancelled_at
 */
#[Fillable([
    'campaign_id',
    'creator_profile_id',
    'creator_offer_id',
    'source',
    'status',
    'booked_price_cents',
    'booked_posts_count',
    'invited_by_user_id',
    'accepted_at',
    'booked_at',
    'cancelled_at',
])]
class Collaboration extends Model
{
    /** @use HasFactory<CollaborationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => CollaborationSource::class,
            'status' => CollaborationStatus::class,
            'accepted_at' => 'datetime',
            'booked_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<CreatorProfile, $this>
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }

    /**
     * @return BelongsTo<CreatorOffer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(CreatorOffer::class, 'creator_offer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return HasMany<CollaborationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(CollaborationEvent::class);
    }

    /**
     * @return HasOne<Conversation, $this>
     */
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    /**
     * @return HasOne<Contract, $this>
     */
    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    /**
     * @return HasOne<Payout, $this>
     */
    public function payout(): HasOne
    {
        return $this->hasOne(Payout::class);
    }

    /**
     * @return HasMany<TrackingLink, $this>
     */
    public function trackingLinks(): HasMany
    {
        return $this->hasMany(TrackingLink::class);
    }
}
