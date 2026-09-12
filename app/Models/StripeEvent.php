<?php

namespace App\Models;

use Database\Factories\StripeEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $stripe_event_id
 * @property string $type
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $processed_at
 */
#[Fillable([
    'stripe_event_id',
    'type',
    'payload',
    'processed_at',
])]
class StripeEvent extends Model
{
    /** @use HasFactory<StripeEventFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
