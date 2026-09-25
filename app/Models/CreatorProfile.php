<?php

namespace App\Models;

use App\Enums\CreatorVettingStatus;
use Database\Factories\CreatorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $display_name
 * @property string|null $linkedin_url
 * @property array<string, mixed>|null $linkedin_profile
 * @property list<array<string, mixed>>|null $linkedin_posts
 * @property string|null $linkedin_verify_code
 * @property Carbon|null $linkedin_verified_at
 * @property Carbon|null $linkedin_synced_at
 * @property string|null $headline
 * @property string|null $photo_path
 * @property string|null $bio
 * @property CreatorVettingStatus $vetting_status
 * @property string|null $stripe_connect_id
 * @property bool $payouts_enabled
 * @property string|null $country
 * @property list<string>|null $industries
 * @property int|null $price_cents
 * @property list<array{posts: int, total_cents: int}>|null $bundles
 * @property Carbon|null $onboarded_at
 */
#[Fillable([
    'user_id',
    'display_name',
    'linkedin_url',
    'linkedin_profile',
    'linkedin_posts',
    'linkedin_verify_code',
    'linkedin_verified_at',
    'linkedin_synced_at',
    'headline',
    'photo_path',
    'bio',
    'vetting_status',
    'stripe_connect_id',
    'payouts_enabled',
    'country',
    'industries',
    'price_cents',
    'bundles',
    'onboarded_at',
])]
class CreatorProfile extends Model
{
    /** @use HasFactory<CreatorProfileFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'industries' => 'array',
            'bundles' => 'array',
            'linkedin_profile' => 'array',
            'linkedin_posts' => 'array',
            'linkedin_verified_at' => 'datetime',
            'linkedin_synced_at' => 'datetime',
            'vetting_status' => CreatorVettingStatus::class,
            'payouts_enabled' => 'boolean',
            'onboarded_at' => 'datetime',
        ];
    }

    public function isLinkedInVerified(): bool
    {
        return $this->linkedin_verified_at !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Niche, $this>
     */
    public function niches(): BelongsToMany
    {
        return $this->belongsToMany(Niche::class, 'creator_niche')
            ->using(CreatorNiche::class)
            ->withTimestamps()
            ->withPivot(['id', 'deleted_at'])
            ->wherePivotNull('deleted_at');
    }

    /**
     * @return HasMany<CreatorAudienceProfile, $this>
     */
    public function audienceProfiles(): HasMany
    {
        return $this->hasMany(CreatorAudienceProfile::class);
    }

    /**
     * @return HasMany<CreatorOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(CreatorOffer::class);
    }

    /**
     * @return HasMany<Collaboration, $this>
     */
    public function collaborations(): HasMany
    {
        return $this->hasMany(Collaboration::class);
    }

    /**
     * @return HasMany<Payout, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }
}
