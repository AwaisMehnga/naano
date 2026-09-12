<?php

namespace App\Models;

use Database\Factories\CompanyIcpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property string $title
 * @property string $description
 * @property int $sort_order
 * @property list<string>|null $tags
 * @property list<string>|null $industries
 * @property list<string>|null $regions
 */
#[Fillable([
    'company_id',
    'title',
    'description',
    'sort_order',
    'tags',
    'industries',
    'regions',
])]
class CompanyIcp extends Model
{
    /** @use HasFactory<CompanyIcpFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'industries' => 'array',
            'regions' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
