<?php

namespace App\Models;

use Database\Factories\CreatorNicheFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'creator_profile_id',
    'niche_id',
])]
class CreatorNiche extends Pivot
{
    /** @use HasFactory<CreatorNicheFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var bool
     */
    public $incrementing = true;

    /**
     * @var string
     */
    protected $table = 'creator_niche';

    /**
     * @return BelongsTo<CreatorProfile, $this>
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }

    /**
     * @return BelongsTo<Niche, $this>
     */
    public function niche(): BelongsTo
    {
        return $this->belongsTo(Niche::class);
    }
}
