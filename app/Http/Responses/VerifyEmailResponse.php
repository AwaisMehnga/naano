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
        $path = HomeRedirect::path($request->user());

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended($path.'?verified=1');
    }
}
