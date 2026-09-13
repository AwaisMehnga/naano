<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Creator\PublishCreatorPostRequest;
use App\Http\Requests\Api\Creator\ScheduleCreatorPostRequest;
use App\Http\Requests\Api\Creator\StoreCreatorPostRequest;
use App\Http\Requests\Api\Creator\UpdateCreatorPostRequest;
use App\Models\Collaboration;
use App\Models\Post;
use App\Models\User;
use App\Services\CreatorPostService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private CreatorPostService $posts) {}

    public function index(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->posts->index($this->actor($request), $collaboration));
    }

    public function store(StoreCreatorPostRequest $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->posts->store(
            $this->actor($request),
            $collaboration,
            $request->validated(),
        ));
    }

    public function update(UpdateCreatorPostRequest $request, Post $post): JsonResponse
    {
        return AjaxResponse::success($this->posts->update(
            $this->actor($request),
            $post,
            $request->validated(),
        ));
    }

    public function submit(Request $request, Post $post): JsonResponse
    {
        return AjaxResponse::success($this->posts->submit($this->actor($request), $post));
    }

    public function schedule(ScheduleCreatorPostRequest $request, Post $post): JsonResponse
    {
        return AjaxResponse::success($this->posts->schedule(
            $this->actor($request),
            $post,
            (string) $request->validated('scheduled_at'),
        ));
    }

    public function publish(PublishCreatorPostRequest $request, Post $post): JsonResponse
    {
        return AjaxResponse::success($this->posts->publish(
            $this->actor($request),
            $post,
            (string) $request->validated('published_url'),
            $request->validated('linkedin_post_id'),
        ));
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
