<?php

namespace App\Models;

use Database\Factories\NicheFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable([
    'name',
    'slug',
    'sort_order',
    'is_active',
])]
class Niche extends Model
{
    /** @use HasFactory<NicheFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<CreatorProfile, $this>
     */
    public function creatorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(CreatorProfile::class, 'creator_niche')
            ->using(CreatorNiche::class)
            ->withTimestamps()
            ->withPivot(['id', 'deleted_at'])
            ->wherePivotNull('deleted_at');
    }
}
