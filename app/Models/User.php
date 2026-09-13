<?php

namespace App\Models;

use App\Services\EmailCodeService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $hear_about
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'hear_about'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<CreatorProfile, $this>
     */
    public function creatorProfile(): HasOne
    {
        return $this->hasOne(CreatorProfile::class);
    }

    /**
     * @return HasOne<Company, $this>
     */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    /**
     * @return HasMany<CompanyMember, $this>
     */
    public function companyMemberships(): HasMany
    {
        return $this->hasMany(CompanyMember::class);
    }

    /**
     * @return BelongsToMany<Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_members')
            ->withTimestamps()
            ->withPivot(['id', 'role', 'invited_at', 'joined_at', 'deleted_at'])
            ->wherePivotNull('deleted_at');
    }

    /**
     * @return HasOne<NotificationPreference, $this>
     */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function wantsEmail(string $key): bool
    {
        $prefs = $this->notificationPreference;

        if ($prefs === null) {
            return true;
        }

        return match ($key) {
            'invites' => $prefs->email_invites,
            'applications' => $prefs->email_applications,
            'campaign_updates' => $prefs->email_campaign_updates,
            'messages' => $prefs->email_messages,
            default => true,
        };
    }

    public function side(): ?string
    {
        if ($this->hasRole('creator')) {
            return 'creator';
        }

        if ($this->hasRole('company')) {
            return 'company';
        }

        return null;
    }

    public function isOnboarded(): bool
    {
        return match ($this->side()) {
            'creator' => $this->creatorProfile?->onboarded_at !== null,
            'company' => $this->companies()->whereNotNull('companies.onboarded_at')->exists(),
            default => false,
        };
    }

    public function sendEmailVerificationNotification(): void
    {
        app(EmailCodeService::class)->send($this);
    }
}
