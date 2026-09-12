<?php

namespace App\Models;

use App\Enums\OfferLabel;
use Database\Factories\CreatorOfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $creator_profile_id
 * @property string $network
 * @property OfferLabel $label
 * @property int $posts_count
 * @property int $price_cents
 * @property bool $is_active
 */
#[Fillable([
    'creator_profile_id',
    'network',
    'label',
    'posts_count',
    'price_cents',
    'is_active',
])]
class CreatorOffer extends Model
{
    /** @use HasFactory<CreatorOfferFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'label' => OfferLabel::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CreatorProfile, $this>
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }

    /**
     * @return HasMany<Collaboration, $this>
     */
    public function collaborations(): HasMany
    {
        return $this->hasMany(Collaboration::class);
    }
}
