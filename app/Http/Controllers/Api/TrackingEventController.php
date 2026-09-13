<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTrackingEventRequest;
use App\Services\TrackingPixelService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;

class TrackingEventController extends Controller
{
    public function __construct(private TrackingPixelService $pixel) {}

    public function store(StoreTrackingEventRequest $request, string $slug): JsonResponse
    {
        $recorded = $this->pixel->record($request, $slug, $request->validated());

        return AjaxResponse::success($recorded['payload'])->withCookie($recorded['cookie']);
    }
}
