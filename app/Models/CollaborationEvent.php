<?php

namespace App\Models;

use App\Enums\CollaborationEventType;
use Database\Factories\CollaborationEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $collaboration_id
 * @property int|null $actor_user_id
 * @property CollaborationEventType $type
 * @property string|null $body
 * @property array<string, mixed>|null $meta
 * @property Carbon $occurred_at
 */
#[Fillable([
    'collaboration_id',
    'actor_user_id',
    'type',
    'body',
    'meta',
    'occurred_at',
])]
class CollaborationEvent extends Model
{
    /** @use HasFactory<CollaborationEventFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CollaborationEventType::class,
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Collaboration, $this>
     */
    public function collaboration(): BelongsTo
    {
        return $this->belongsTo(Collaboration::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
