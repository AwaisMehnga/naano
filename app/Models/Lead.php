<?php

namespace App\Models;

use App\Enums\LeadSource;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $campaign_id
 * @property int|null $post_id
 * @property int|null $tracking_link_id
 * @property Carbon $occurred_at
 * @property LeadSource $source
 * @property array<string, mixed>|null $payload
 */
#[Fillable([
    'company_id',
    'campaign_id',
    'post_id',
    'tracking_link_id',
    'occurred_at',
    'source',
    'payload',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'source' => LeadSource::class,
            'payload' => 'array',
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
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<TrackingLink, $this>
     */
    public function trackingLink(): BelongsTo
    {
        return $this->belongsTo(TrackingLink::class);
    }
}
