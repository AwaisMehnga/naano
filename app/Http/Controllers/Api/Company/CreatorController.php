<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\IndexCompanyCreatorsRequest;
use App\Models\CreatorProfile;
use App\Services\CompanyCreatorDiscoveryService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;

class CreatorController extends Controller
{
    public function __construct(private CompanyCreatorDiscoveryService $discovery) {}

    public function index(IndexCompanyCreatorsRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->discovery->index($request->validated()));
    }

    public function show(CreatorProfile $creatorProfile): JsonResponse
    {
        return AjaxResponse::success($this->discovery->show($creatorProfile));
    }
}
