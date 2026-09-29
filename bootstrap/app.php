<?php

use App\Enums\ActivityAction;
use App\Services\ActivityLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // SetLocale must precede HandleInertiaRequests: Inertia evaluates
        // its shared props on the way *in*, so anything locale-dependent
        // (the `locale` prop, the settings read per locale) is resolved
        // before a later middleware could set the locale.
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Trust only the local reverse proxy for scheme/host. Trusting
        // '*' would let a client spoof X-Forwarded-For and slip past the
        // IP-keyed rate limiters, so the list stays explicit.
        $middleware->trustProxies(
            at: array_filter(explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1')))
        );

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminAccess::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'admin.session' => \App\Http\Middleware\EnsureAdminSessionIsFresh::class,
            'password.fresh' => \App\Http\Middleware\RequirePasswordChange::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        ]);

        // Binds the session to the authenticated user, so changing a
        // password invalidates every other device's session.
        $middleware->appendToGroup('auth.session', [
            \Illuminate\Session\Middleware\AuthenticateSession::class,
        ]);

        // CSRF applies to every state-changing web request; nothing is
        // exempted. API routes use Sanctum's stateful guard instead.
        $middleware->validateCsrfTokens(except: []);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A denied authorization attempt is a security signal worth
        // keeping, but only for signed-in users (guests just get
        // redirected to the login page by the auth middleware).
        $exceptions->report(function (AuthorizationException $e) {
            $request = request();

            if (! $request?->user()) {
                return;
            }

            app(ActivityLogger::class)->log(
                ActivityAction::UnauthorizedAttempt,
                null,
                [
                    'path' => $request->path(),
                    'route' => $request->route()?->getName(),
                    'message' => $e->getMessage(),
                ],
                description: 'محاولة تنفيذ إجراء بدون صلاحية',
            );
        });

        // Never echo internal details back to the browser in production.
        $exceptions->respond(function ($response, \Throwable $e, Request $request) {
            if (app()->hasDebugModeEnabled() || ! in_array($response->getStatusCode(), [403, 419, 429, 500, 503], true)) {
                return $response;
            }

            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                return $response;
            }

            return match ($response->getStatusCode()) {
                419 => back()->withErrors(['error' => __('auth.session_expired')]),
                429 => back()->withErrors(['error' => __('auth.throttle_generic')]),
                default => $response,
            };
        });
    })->create();
