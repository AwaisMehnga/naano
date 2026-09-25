<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Creator\StoreMediaRequest;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function store(StoreMediaRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->media->store(
            $this->actor($request),
            $request->file('file'),
        ));
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        $this->media->destroy($this->actor($request), $media);

        return AjaxResponse::success(['deleted' => true]);
    }

    private function actor(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
