<?php

namespace App\Services;

use App\Enums\CompanyMemberRole;
use App\Models\Company;
use App\Models\CompanyInvite;
use App\Models\CompanyMember;
use App\Models\User;
use App\Notifications\CompanyMemberInvite;
use App\Support\AuthMail;
use App\Support\CompanyAccess;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyMemberService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function index(Company $company): array
    {
        $members = CompanyMember::query()
            ->where('company_id', $company->id)
            ->with('user')
            ->orderBy('id')
            ->get()
            ->map(fn (CompanyMember $member): array => $this->payload($member))
            ->all();

        $invites = CompanyInvite::query()
            ->where('company_id', $company->id)
            ->whereNull('accepted_at')
            ->orderBy('id')
            ->get()
            ->map(fn (CompanyInvite $invite): array => $this->invitePayload($invite))
            ->all();

        return [...$members, ...$invites];
    }

    /**
     * @return array<string, mixed>
     */
    public function invite(User $actor, Company $company, string $email, CompanyMemberRole $role): array
    {
        $this->ensureOwner($actor, $company);

        $email = Str::lower($email);
        $invitee = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($invitee?->hasRole('creator')) {
            throw ValidationException::withMessages([
                'email' => ['This email is registered as a creator.'],
            ]);
        }

        if ($invitee?->hasRole('company')) {
            return $this->addExistingMember($company, $invitee, $role);
        }

        return $this->sendPendingInvite($actor, $company, $email, $role);
    }

    public function acceptPendingInvites(User $user): void
    {
        if (! $user->hasRole('company')) {
            return;
        }

        $invites = CompanyInvite::query()
            ->where('email', Str::lower($user->email))
            ->whereNull('accepted_at')
            ->orderBy('id')
            ->get();

        foreach ($invites as $invite) {
            $exists = CompanyMember::query()
                ->where('company_id', $invite->company_id)
                ->where('user_id', $user->id)
                ->exists();

            if (! $exists) {
                CompanyMember::query()->create([
                    'company_id' => $invite->company_id,
                    'user_id' => $user->id,
                    'role' => $invite->role,
                    'invited_at' => $invite->created_at,
                    'joined_at' => now(),
                ]);
            }

            $invite->accepted_at = now();
            $invite->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function updateRole(User $actor, Company $company, CompanyMember $member, CompanyMemberRole $role): array
    {
        $this->ensureOwner($actor, $company);
        $this->ensureOwned($company, $member);

        if ($member->role === CompanyMemberRole::Owner && $role !== CompanyMemberRole::Owner) {
            if (CompanyAccess::ownerCount($company) === 1) {
                throw ValidationException::withMessages([
                    'role' => ['The last owner cannot be demoted.'],
                ]);
            }
        }

        $member->role = $role;
        $member->save();
        $member->load('user');

        return $this->payload($member);
    }

    public function destroy(User $actor, Company $company, CompanyMember $member): void
    {
        $this->ensureOwner($actor, $company);
        $this->ensureOwned($company, $member);

        if ($member->role === CompanyMemberRole::Owner && CompanyAccess::ownerCount($company) === 1) {
            throw ValidationException::withMessages([
                'member' => ['The last owner cannot be removed.'],
            ]);
        }

        $member->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function addExistingMember(Company $company, User $invitee, CompanyMemberRole $role): array
    {
        $exists = CompanyMember::query()
            ->where('company_id', $company->id)
            ->where('user_id', $invitee->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'email' => ['This person is already a member.'],
            ]);
        }

        $member = CompanyMember::query()->create([
            'company_id' => $company->id,
            'user_id' => $invitee->id,
            'role' => $role,
            'invited_at' => now(),
            'joined_at' => now(),
        ]);

        $member->load('user');

        return $this->payload($member);
    }

    /**
     * @return array<string, mixed>
     */
    private function sendPendingInvite(User $actor, Company $company, string $email, CompanyMemberRole $role): array
    {
        $invite = CompanyInvite::query()
            ->where('company_id', $company->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->first();

        if ($invite === null) {
            $invite = CompanyInvite::query()->create([
                'company_id' => $company->id,
                'email' => $email,
                'role' => $role,
                'invited_by_user_id' => $actor->id,
            ]);
        } else {
            $invite->role = $role;
            $invite->invited_by_user_id = $actor->id;
            $invite->save();
        }

        AuthMail::once('invite:'.$company->id.':'.$email, 15, function () use ($company, $actor, $role, $email): void {
            Notification::route('mail', $email)->notify(
                new CompanyMemberInvite($company, $actor, $role, $email),
            );
        });

        return $this->invitePayload($invite);
    }

    private function ensureOwner(User $actor, Company $company): void
    {
        if (! CompanyAccess::canManageMoney($actor, $company)) {
            abort(403, 'Only owners can manage team access.');
        }
    }

    private function ensureOwned(Company $company, CompanyMember $member): void
    {
        if ($member->company_id !== $company->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CompanyMember $member): array
    {
        return [
            'id' => $member->id,
            'user_id' => $member->user_id,
            'name' => $member->user?->name,
            'email' => $member->user?->email,
            'role' => $member->role->value,
            'joined_at' => $member->joined_at?->toIso8601String(),
            'status' => 'joined',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invitePayload(CompanyInvite $invite): array
    {
        return [
            'id' => $invite->id,
            'user_id' => null,
            'name' => null,
            'email' => $invite->email,
            'role' => $invite->role->value,
            'joined_at' => null,
            'status' => 'pending',
        ];
    }
}
