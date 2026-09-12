<?php

namespace App\Http\Controllers;

use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function onboardingDone(Request $request, string $url, string $message = 'OK'): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return AjaxResponse::success(['redirect' => $url], $message);
        }

        return redirect($url);
    }
}
