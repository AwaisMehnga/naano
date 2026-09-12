<?php

namespace App\Models;

use App\Enums\CampaignObjective;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $company_icp_id
 * @property string $name
 * @property CampaignType $type
 * @property CampaignObjective $objective
 * @property CampaignStatus $status
 * @property int|null $budget_cents
 * @property string|null $goal
 * @property list<string>|null $key_messages
 * @property string|null $guidelines
 * @property Carbon|null $start_at
 * @property Carbon|null $end_at
 * @property int $created_by_user_id
 */
#[Fillable([
    'company_id',
    'company_icp_id',
    'name',
    'type',
    'objective',
    'status',
    'budget_cents',
    'goal',
    'key_messages',
    'guidelines',
    'start_at',
    'end_at',
    'created_by_user_id',
])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CampaignType::class,
            'objective' => CampaignObjective::class,
            'status' => CampaignStatus::class,
            'key_messages' => 'array',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
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
     * @return BelongsTo<CompanyIcp, $this>
     */
    public function companyIcp(): BelongsTo
    {
        return $this->belongsTo(CompanyIcp::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<Collaboration, $this>
     */
    public function collaborations(): HasMany
    {
        return $this->hasMany(Collaboration::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return HasMany<CreatorMatchScore, $this>
     */
    public function matchScores(): HasMany
    {
        return $this->hasMany(CreatorMatchScore::class);
    }
}
