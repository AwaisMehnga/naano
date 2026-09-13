<?php

use App\Exceptions\InvalidStripeSignatureException;
use App\Exceptions\WalletUnderfundedException;
use App\Http\Middleware\EnsureCurrentCompany;
use App\Http\Middleware\EnsureOnboarded;
use App\Http\Middleware\HandleAppearance;
use App\Support\AjaxResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('api')
                ->name('api.')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'naano_vid']);

        $middleware->validateCsrfTokens(except: [
            'api/stripe/webhook',
            'api/t/*',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'onboarded' => EnsureOnboarded::class,
            'current.company' => EnsureCurrentCompany::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof AuthenticationException) {
                return AjaxResponse::error('Unauthenticated.', status: 401);
            }

            if ($e instanceof ValidationException) {
                return AjaxResponse::error($e->getMessage(), $e->errors(), 422);
            }

            if ($e instanceof InvalidStripeSignatureException) {
                return AjaxResponse::error($e->getMessage(), status: 400);
            }

            if ($e instanceof WalletUnderfundedException) {
                return AjaxResponse::error($e->getMessage(), $e->payload, 422);
            }

            if ($e instanceof HttpExceptionInterface) {
                $message = $e->getMessage() !== '' ? $e->getMessage() : 'Request failed.';

                return AjaxResponse::error($message, status: $e->getStatusCode());
            }

            return AjaxResponse::failure(
                app()->hasDebugModeEnabled() ? $e->getMessage() : 'Server error.',
            );
        });
    })->create();
