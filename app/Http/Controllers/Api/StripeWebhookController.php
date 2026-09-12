<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Stripe\StripeGateway;
use App\Services\StripeWebhookService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __construct(
        private StripeGateway $stripe,
        private StripeWebhookService $webhooks,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $event = $this->stripe->parseWebhook(
            $request->getContent(),
            (string) $request->header('Stripe-Signature', ''),
        );

        $this->webhooks->handle($event);

        return AjaxResponse::success(['received' => true]);
    }
}
