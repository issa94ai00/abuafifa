<?php

namespace App\Http\Middleware;

use App\Enums\ActivityAction;
use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The single door into the back office.
 *
 * Passing this middleware only proves the user is *staff*; what they may
 * actually do is decided per action by the policies. Keep both — the
 * middleware is the perimeter, the policies are the locks inside.
 */
class AdminAccess
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessAdmin()) {
            $this->logger->log(
                ActivityAction::UnauthorizedAttempt,
                null,
                [
                    'path' => $request->path(),
                    'reason' => $user ? 'not_staff_or_inactive' : 'guest',
                ],
                description: 'محاولة وصول إلى لوحة التحكم بدون صلاحية',
            );

            abort(403, __('auth.admin_only'));
        }

        return $next($request);
    }
}
