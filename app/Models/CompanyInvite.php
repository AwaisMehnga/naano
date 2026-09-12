<?php

namespace App\Models;

use App\Enums\CompanyMemberRole;
use Database\Factories\CompanyInviteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $email
 * @property CompanyMemberRole $role
 * @property int $invited_by_user_id
 * @property Carbon|null $accepted_at
 */
#[Fillable([
    'company_id',
    'email',
    'role',
    'invited_by_user_id',
    'accepted_at',
])]
class CompanyInvite extends Model
{
    /** @use HasFactory<CompanyInviteFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => CompanyMemberRole::class,
            'accepted_at' => 'datetime',
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
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
}
