<?php

namespace App\Models;

use Database\Factories\CreatorMatchScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int $creator_profile_id
 * @property int $fit_score
 * @property list<string>|null $reasons
 * @property Carbon $computed_at
 */
#[Fillable([
    'campaign_id',
    'creator_profile_id',
    'fit_score',
    'reasons',
    'computed_at',
])]
class CreatorMatchScore extends Model
{
    /** @use HasFactory<CreatorMatchScoreFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reasons' => 'array',
            'computed_at' => 'datetime',
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
}
