<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\StoreCompanyIcpRequest;
use App\Http\Requests\Api\Company\UpdateCompanyIcpRequest;
use App\Models\CompanyIcp;
use App\Services\CompanyIcpService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IcpController extends Controller
{
    public function __construct(
        private CompanyIcpService $icps,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->icps->index($this->currentCompany->fromRequest($request)));
    }

    public function store(StoreCompanyIcpRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->icps->store(
            $this->currentCompany->fromRequest($request),
            $request->validated(),
        ));
    }

    public function update(UpdateCompanyIcpRequest $request, CompanyIcp $icp): JsonResponse
    {
        return AjaxResponse::success($this->icps->update(
            $this->currentCompany->fromRequest($request),
            $icp,
            $request->validated(),
        ));
    }

    public function destroy(Request $request, CompanyIcp $icp): JsonResponse
    {
        $this->icps->destroy($this->currentCompany->fromRequest($request), $icp);

        return AjaxResponse::success([]);
    }
}
