<?php

namespace App\Models;

use Database\Factories\CreatorAudienceProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $creator_profile_id
 * @property string $network
 * @property int|null $followers_count
 * @property array<string, mixed>|null $audience_mix
 * @property Carbon|null $captured_at
 */
#[Fillable([
    'creator_profile_id',
    'network',
    'followers_count',
    'audience_mix',
    'captured_at',
])]
class CreatorAudienceProfile extends Model
{
    /** @use HasFactory<CreatorAudienceProfileFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience_mix' => 'array',
            'captured_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CreatorProfile, $this>
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }
}
