<?php

namespace App\Models;

use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use Database\Factories\WalletTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $wallet_id
 * @property int|null $campaign_id
 * @property int|null $collaboration_id
 * @property WalletTransactionType $type
 * @property WalletTransactionDirection $direction
 * @property int $amount_cents
 * @property WalletTransactionStatus $status
 * @property string|null $stripe_id
 */
#[Fillable([
    'wallet_id',
    'campaign_id',
    'collaboration_id',
    'type',
    'direction',
    'amount_cents',
    'status',
    'stripe_id',
])]
class WalletTransaction extends Model
{
    /** @use HasFactory<WalletTransactionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'direction' => WalletTransactionDirection::class,
            'status' => WalletTransactionStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<Collaboration, $this>
     */
    public function collaboration(): BelongsTo
    {
        return $this->belongsTo(Collaboration::class);
    }
}
