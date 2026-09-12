<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use Database\Factories\PayoutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $creator_profile_id
 * @property int $collaboration_id
 * @property int $amount_cents
 * @property PayoutStatus $status
 * @property string|null $stripe_transfer_id
 * @property Carbon|null $paid_at
 */
#[Fillable([
    'creator_profile_id',
    'collaboration_id',
    'amount_cents',
    'status',
    'stripe_transfer_id',
    'paid_at',
])]
class Payout extends Model
{
    /** @use HasFactory<PayoutFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PayoutStatus::class,
            'paid_at' => 'datetime',
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
     * @return BelongsTo<Collaboration, $this>
     */
    public function collaboration(): BelongsTo
    {
        return $this->belongsTo(Collaboration::class);
    }
}
