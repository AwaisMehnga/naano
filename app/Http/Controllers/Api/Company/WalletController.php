<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\IndexCompanyWalletTransactionsRequest;
use App\Http\Requests\Api\Company\StoreCompanyWalletTopupRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\CompanyWalletService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private CompanyWalletService $wallets,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->wallets->show(
            $this->currentCompany->fromRequest($request),
        ));
    }

    public function transactions(IndexCompanyWalletTransactionsRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->wallets->transactions(
            $this->currentCompany->fromRequest($request),
            $request->validated(),
        ));
    }

    public function storeTopup(StoreCompanyWalletTopupRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->wallets->startTopup(
            $this->currentCompany->fromRequest($request),
            $this->actor($request),
            (int) $request->validated('amount_cents'),
        ));
    }

    public function showTopup(Request $request, WalletTransaction $walletTransaction): JsonResponse
    {
        return AjaxResponse::success($this->wallets->showTopup(
            $this->currentCompany->fromRequest($request),
            $walletTransaction,
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
