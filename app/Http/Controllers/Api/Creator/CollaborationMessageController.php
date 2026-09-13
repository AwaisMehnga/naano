<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Creator\StoreCreatorCollaborationMessageRequest;
use App\Models\Collaboration;
use App\Models\User;
use App\Services\CollaborationMessageService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollaborationMessageController extends Controller
{
    public function __construct(private CollaborationMessageService $messages) {}

    public function index(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->messages->indexForCreator(
            $this->actor($request),
            $collaboration,
        ));
    }

    public function store(StoreCreatorCollaborationMessageRequest $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->messages->storeForCreator(
            $this->actor($request),
            $collaboration,
            $request->validated('body'),
        ));
    }

    public function read(Request $request, Collaboration $collaboration): JsonResponse
    {
        return AjaxResponse::success($this->messages->markReadForCreator(
            $this->actor($request),
            $collaboration,
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
