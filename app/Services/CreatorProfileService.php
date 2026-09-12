<?php

namespace App\Services;

use App\Models\CreatorNiche;
use App\Models\CreatorProfile;
use App\Models\Niche;
use App\Models\User;
use App\Support\PublicDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CreatorProfileService
{
    /**
     * @return array<string, mixed>
     */
    public function show(User $user): array
    {
        return $this->payload($this->profile($user));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(User $user, array $data, ?UploadedFile $photo = null, bool $removePhoto = false): array
    {
        $profile = $this->profile($user);

        if ($photo instanceof UploadedFile) {
            $path = $photo->store('creators/'.$profile->id, 'public');

            if (! is_string($path) || $path === '') {
                throw ValidationException::withMessages([
                    'photo' => 'The photo could not be stored.',
                ]);
            }

            if (is_string($profile->photo_path) && $profile->photo_path !== '') {
                Storage::disk('public')->delete($profile->photo_path);
            }

            $data['photo_path'] = $path;
        } elseif ($removePhoto) {
            if (is_string($profile->photo_path) && $profile->photo_path !== '') {
                Storage::disk('public')->delete($profile->photo_path);
            }

            $data['photo_path'] = null;
        }

        $profile->fill($data);
        $profile->save();

        return $this->payload($profile->fresh() ?? $profile);
    }

    /**
     * @param  list<int>  $nicheIds
     * @return array<string, mixed>
     */
    public function replaceNiches(User $user, array $nicheIds): array
    {
        $profile = $this->profile($user);
        $uniqueIds = array_values(array_unique(array_map(intval(...), $nicheIds)));

        $niches = Niche::query()
            ->whereIn('id', $uniqueIds)
            ->where('is_active', true)
            ->get();

        if ($niches->count() !== count($uniqueIds)) {
            throw ValidationException::withMessages([
                'niche_ids' => ['One or more niches are invalid.'],
            ]);
        }

        CreatorNiche::query()
            ->where('creator_profile_id', $profile->id)
            ->get()
            ->each(function (CreatorNiche $row) use ($uniqueIds): void {
                if (! in_array($row->niche_id, $uniqueIds, true)) {
                    $row->delete();
                }
            });

        foreach ($niches as $niche) {
            $existing = CreatorNiche::withTrashed()
                ->where('creator_profile_id', $profile->id)
                ->where('niche_id', $niche->id)
                ->first();

            if ($existing instanceof CreatorNiche) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                continue;
            }

            CreatorNiche::query()->create([
                'creator_profile_id' => $profile->id,
                'niche_id' => $niche->id,
            ]);
        }

        return $this->payload($profile->fresh() ?? $profile);
    }

    public function profile(User $user): CreatorProfile
    {
        $profile = $user->creatorProfile;

        if (! $profile instanceof CreatorProfile) {
            abort(404, 'Creator profile not found.');
        }

        return $profile;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CreatorProfile $profile): array
    {
        $profile->load(['niches' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')]);

        return [
            'id' => $profile->id,
            'display_name' => $profile->display_name,
            'linkedin_url' => $profile->linkedin_url,
            'headline' => $profile->headline,
            'photo_url' => PublicDisk::url($profile->photo_path),
            'bio' => $profile->bio,
            'country' => $profile->country,
            'vetting_status' => $profile->vetting_status->value,
            'niches' => $profile->niches->map(fn (Niche $niche): array => [
                'id' => $niche->id,
                'name' => $niche->name,
                'slug' => $niche->slug,
            ])->values()->all(),
        ];
    }
}
