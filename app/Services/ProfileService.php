<?php

namespace App\Services;

use App\Enums\ProfileType;
use App\Models\Company;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function __construct(private ActiveProfileService $activeProfile) {}

    /**
     * @return array{profiles: list<array{type: string, onboarded: bool, label: string}>, active_profile: string|null, can_create: list<string>}
     */
    public function index(User $user, Request $request): array
    {
        return [
            'profiles' => $this->activeProfile->listFor($user),
            'active_profile' => $this->activeProfile->type($request)?->value,
            'can_create' => $this->creatableTypes($user),
        ];
    }

    /**
     * @return array{profiles: list<array{type: string, onboarded: bool, label: string}>, active_profile: string, can_create: list<string>}
     */
    public function createCreator(User $user, Request $request): array
    {
        if ($user->creatorProfile !== null) {
            abort(422, 'Creator profile already exists.');
        }

        DB::transaction(function () use ($user): void {
            $user->creatorProfile()->create([
                'display_name' => $user->name,
            ]);
        });

        $user->refresh();
        $this->activeProfile->activate($user, $request, ProfileType::Creator);

        return [
            'profiles' => $this->activeProfile->listFor($user),
            'active_profile' => ProfileType::Creator->value,
            'can_create' => $this->creatableTypes($user),
        ];
    }

    /**
     * @return array{profiles: list<array{type: string, onboarded: bool, label: string}>, active_profile: string, can_create: list<string>}
     */
    public function createCompany(User $user, Request $request): array
    {
        if ($user->company !== null) {
            abort(422, 'Company profile already exists.');
        }

        DB::transaction(function () use ($user): void {
            $user->company()->create([]);
        });

        $user->refresh();
        $this->activeProfile->activate($user, $request, ProfileType::Company);

        return [
            'profiles' => $this->activeProfile->listFor($user),
            'active_profile' => ProfileType::Company->value,
            'can_create' => $this->creatableTypes($user),
        ];
    }

    /**
     * @return array{profiles: list<array{type: string, onboarded: bool, label: string}>, active_profile: string, can_create: list<string>}
     */
    public function activate(User $user, Request $request, ProfileType $type): array
    {
        $this->activeProfile->activate($user, $request, $type);

        return [
            'profiles' => $this->activeProfile->listFor($user),
            'active_profile' => $type->value,
            'can_create' => $this->creatableTypes($user),
        ];
    }

    public function provision(User $user, ProfileType $type): CreatorProfile|Company
    {
        return match ($type) {
            ProfileType::Creator => $user->creatorProfile()->firstOrCreate(
                ['user_id' => $user->id],
                ['display_name' => $user->name],
            ),
            ProfileType::Company => $user->company ?? $user->company()->create([]),
            default => abort(422, 'That profile type is not available yet.'),
        };
    }

    /**
     * @return list<string>
     */
    private function creatableTypes(User $user): array
    {
        $types = [];

        if ($user->creatorProfile === null) {
            $types[] = ProfileType::Creator->value;
        }

        if ($user->company === null) {
            $types[] = ProfileType::Company->value;
        }

        return $types;
    }
}
