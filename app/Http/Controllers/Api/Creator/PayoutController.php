<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\User;
use App\Services\CreatorWalletService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(private CreatorWalletService $wallets) {}

    public function index(Request $request): JsonResponse
    {
        return AjaxResponse::success($this->wallets->payouts($this->actor($request)));
    }

    public function show(Request $request, Payout $payout): JsonResponse
    {
        return AjaxResponse::success($this->wallets->payout($this->actor($request), $payout));
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
