<?php

namespace App\Models;

use App\Enums\CompanyMemberRole;
use Database\Factories\CompanyMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property CompanyMemberRole $role
 * @property Carbon|null $invited_at
 * @property Carbon|null $joined_at
 */
#[Fillable([
    'company_id',
    'user_id',
    'role',
    'invited_at',
    'joined_at',
])]
class CompanyMember extends Model
{
    /** @use HasFactory<CompanyMemberFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => CompanyMemberRole::class,
            'invited_at' => 'datetime',
            'joined_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
