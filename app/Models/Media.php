<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property string $kind
 * @property int $size_bytes
 * @property string|null $original_name
 * @property string|null $mediable_type
 * @property int|null $mediable_id
 * @property array<string, mixed>|null $meta
 */
#[Fillable([
    'user_id',
    'disk',
    'path',
    'mime_type',
    'kind',
    'size_bytes',
    'original_name',
    'mediable_type',
    'mediable_id',
    'meta',
])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'size_bytes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
