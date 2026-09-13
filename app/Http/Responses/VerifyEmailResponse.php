<?php

namespace App\Http\Responses;

use App\Support\HomeRedirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $target = HomeRedirect::afterAuth($request);
        $separator = str_contains($target, '?') ? '&' : '?';

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect($target.$separator.'verified=1');
    }
}
