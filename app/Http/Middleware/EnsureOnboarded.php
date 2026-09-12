<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AjaxResponse;
use App\Support\HomeRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboarded
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->isOnboarded()) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return AjaxResponse::error('Finish onboarding first.', status: 403);
        }

        return redirect(HomeRedirect::path($user));
    }
}
