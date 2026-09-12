<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NicheService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;

class NicheController extends Controller
{
    public function __construct(private NicheService $niches) {}

    public function index(): JsonResponse
    {
        return AjaxResponse::success($this->niches->active());
    }
}
