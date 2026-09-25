<?php

namespace App\Http\Middleware;

use App\Enums\ProfileType;
use App\Models\User;
use App\Services\ActiveProfileService;
use App\Support\AjaxResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveProfile
{
    public function __construct(private ActiveProfileService $activeProfile) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $profile): Response
    {
        $type = ProfileType::tryFrom($profile);

        if (! $type instanceof ProfileType || ! $type->isAvailable()) {
            abort(500, 'Invalid profile middleware.');
        }

        $user = $request->user();

        if (! $user instanceof User || ! $this->activeProfile->userOwns($user, $type)) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return AjaxResponse::error('Create that profile first.', status: 403);
            }

            return redirect()->route('profiles.choose');
        }

        $this->activeProfile->activate($user, $request, $type);

        return $next($request);
    }
}
