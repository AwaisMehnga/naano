<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property string|null $website
 * @property string|null $logo_path
 * @property string|null $billing_email
 * @property string|null $country
 * @property string|null $stripe_customer_id
 * @property string|null $value_proposition
 * @property list<array{title: string, description: string}>|null $icps
 * @property array{industries?: list<string>, regions?: list<string>, titles?: list<string>, seniority?: list<string>, company_sizes?: list<string>}|null $audience_targeting
 * @property Carbon|null $onboarded_at
 */
#[Fillable([
    'user_id',
    'name',
    'website',
    'logo_path',
    'billing_email',
    'country',
    'stripe_customer_id',
    'value_proposition',
    'icps',
    'audience_targeting',
    'onboarded_at',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'icps' => 'array',
            'audience_targeting' => 'array',
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

    /**
     * @return HasMany<CompanyMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(CompanyMember::class);
    }

    /**
     * @return HasMany<CompanyInvite, $this>
     */
    public function invites(): HasMany
    {
        return $this->hasMany(CompanyInvite::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_members')
            ->withTimestamps()
            ->withPivot(['id', 'role', 'invited_at', 'joined_at', 'deleted_at'])
            ->wherePivotNull('deleted_at');
    }

    /**
     * @return HasMany<CompanyIcp, $this>
     */
    public function companyIcps(): HasMany
    {
        return $this->hasMany(CompanyIcp::class);
    }

    /**
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * @return HasOne<Wallet, $this>
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<CompanySubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class);
    }
}
