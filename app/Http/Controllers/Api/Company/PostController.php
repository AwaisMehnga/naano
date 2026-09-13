<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\RejectCompanyPostRequest;
use App\Http\Requests\Api\Company\StoreCompanyPostChangesRequest;
use App\Models\Collaboration;
use App\Models\Post;
use App\Models\User;
use App\Services\CompanyPostService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        private CompanyPostService $posts,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function index(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->posts->index(
            $this->currentCompany->fromRequest($request),
            $collaboration,
        ));
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        return AjaxResponse::success($this->posts->show(
            $this->currentCompany->fromRequest($request),
            $post,
        ));
    }

    public function approve(Request $request, Post $post): JsonResponse
    {
        return AjaxResponse::success($this->posts->approve(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $post,
        ));
    }

    public function changes(StoreCompanyPostChangesRequest $request, Post $post): JsonResponse
    {
        return AjaxResponse::success($this->posts->requestChanges(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $post,
            (string) $request->validated('review_note'),
        ));
    }

    public function reject(RejectCompanyPostRequest $request, Post $post): JsonResponse
    {
        return AjaxResponse::success($this->posts->reject(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            $post,
            $request->validated('review_note'),
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
