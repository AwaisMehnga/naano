<?php

namespace App\Models;

use App\Enums\ProfileType;
use App\Services\ActiveProfileService;
use App\Services\EmailCodeService;
use App\Support\AuthMail;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
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
            default => true,
        };
    }

    public function ownsProfile(ProfileType $type): bool
    {
        return app(ActiveProfileService::class)->userOwns($this, $type);
    }

    public function activeProfileType(?Request $request = null): ?ProfileType
    {
        return app(ActiveProfileService::class)->type($request ?? request());
    }

    /**
     * @deprecated Use activeProfileType() — kept for transitional call sites.
     */
    public function side(): ?string
    {
        return $this->activeProfileType()?->value
            ?? app(ActiveProfileService::class)->defaultType($this)?->value;
    }

    public function isOnboarded(?ProfileType $type = null): bool
    {
        $type ??= $this->activeProfileType()
            ?? app(ActiveProfileService::class)->defaultType($this);

        if (! $type instanceof ProfileType) {
            return false;
        }

        return app(ActiveProfileService::class)->isOnboarded($this, $type);
    }

    public function sendEmailVerificationNotification(): void
    {
        app(EmailCodeService::class)->send($this);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] mixed $token): void
    {
        AuthMail::once('reset:'.$this->id, 60, function () use ($token): void {
            $this->notify(new ResetPassword($token));
        });
    }
}
