<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use App\Support\PublicDisk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaService
{
    /**
     * @return array<string, mixed>
     */
    public function store(User $user, UploadedFile $file): array
    {
        $mime = (string) $file->getMimeType();
        $kind = str_starts_with($mime, 'image/')
            ? 'image'
            : (str_starts_with($mime, 'video/') ? 'video' : null);

        if ($kind === null) {
            throw ValidationException::withMessages([
                'file' => 'Only photo or video files can be uploaded.',
            ]);
        }

        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $path = $file->storeAs(
            'media/'.$user->id,
            Str::uuid()->toString().'.'.$extension,
            'public',
        );

        $media = Media::query()->create([
            'user_id' => $user->id,
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $mime,
            'kind' => $kind,
            'size_bytes' => $file->getSize() ?: 0,
            'original_name' => $file->getClientOriginalName(),
            'meta' => null,
        ]);

        return $this->payload($media);
    }

    public function destroy(User $user, Media $media): void
    {
        if ($media->user_id !== $user->id) {
            abort(404);
        }

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }

    /**
     * @param  list<int>  $mediaIds
     */
    public function syncFor(User $user, Model $mediable, array $mediaIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $mediaIds)));

        $owned = Media::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($mediable): void {
                $query->whereNull('mediable_id')
                    ->orWhere(function ($attached) use ($mediable): void {
                        $attached->where('mediable_type', $mediable->getMorphClass())
                            ->where('mediable_id', $mediable->getKey());
                    });
            })
            ->whereIn('id', $ids === [] ? [0] : $ids)
            ->get();

        if ($owned->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'media_ids' => 'One or more media files are not available.',
            ]);
        }

        Media::query()
            ->where('mediable_type', $mediable->getMorphClass())
            ->where('mediable_id', $mediable->getKey())
            ->whereNotIn('id', $ids === [] ? [0] : $ids)
            ->update([
                'mediable_type' => null,
                'mediable_id' => null,
            ]);

        foreach ($owned as $media) {
            $media->mediable()->associate($mediable);
            $media->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Media $media): array
    {
        return [
            'id' => $media->id,
            'kind' => $media->kind,
            'mime_type' => $media->mime_type,
            'size_bytes' => $media->size_bytes,
            'original_name' => $media->original_name,
            'url' => PublicDisk::url($media->path),
        ];
    }
}
