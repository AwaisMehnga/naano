<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Creator\StoreCreatorWithdrawalRequest;
use App\Models\User;
use App\Services\CreatorWalletService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreatorWalletController extends Controller
{
    public function __construct(private CreatorWalletService $wallets) {}

    public function show(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->wallets->show($this->actor($request)));
    }

    public function transactions(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->wallets->transactions($this->actor($request)));
    }

    public function storeWithdrawal(StoreCreatorWithdrawalRequest $request): JsonResponse
    {
        return AjaxResponse::success($this->wallets->withdraw(
            $this->actor($request),
            (int) $request->validated('amount_cents'),
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
