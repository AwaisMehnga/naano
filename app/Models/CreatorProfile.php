<?php

namespace App\Models;

use Database\Factories\CreatorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $linkedin_url
 * @property string|null $headline
 * @property string|null $photo_path
 * @property string|null $country
 * @property list<string>|null $industries
 * @property int|null $price_cents
 * @property list<array{posts: int, total_cents: int}>|null $bundles
 * @property Carbon|null $onboarded_at
 */
#[Fillable([
    'user_id',
    'linkedin_url',
    'headline',
    'photo_path',
    'country',
    'industries',
    'price_cents',
    'bundles',
    'onboarded_at',
])]
class CreatorProfile extends Model
{
    /** @use HasFactory<CreatorProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'industries' => 'array',
            'bundles' => 'array',
            'onboarded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
