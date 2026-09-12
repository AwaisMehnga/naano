<?php

namespace App\Http\Middleware;

use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentCompany
{
    public function __construct(private CurrentCompanyService $currentCompany) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $company = $this->currentCompany->resolve($request);

        if ($company === null) {
            return AjaxResponse::error('No company workspace.', status: 403);
        }

        $request->attributes->set('currentCompany', $company);

        return $next($request);
    }
}
