<?php

namespace App\Http\Middleware;

use App\Enums\ProfileType;
use App\Models\User;
use App\Services\ActiveProfileService;
use App\Support\AjaxResponse;
use App\Support\HomeRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboarded
{
    public function __construct(private ActiveProfileService $activeProfile) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $type = $this->activeProfile->type($request) ?? $this->activeProfile->defaultType($user);

        if ($type instanceof ProfileType && $this->activeProfile->isOnboarded($user, $type)) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return AjaxResponse::error('Finish onboarding first.', status: 403);
        }

        return redirect(HomeRedirect::path($user, $request));
    }
}
