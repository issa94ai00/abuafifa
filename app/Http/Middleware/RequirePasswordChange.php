<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds a user on the password form until they have replaced the
 * password an administrator set for them.
 *
 * Without this the `must_change_password` flag would be decoration:
 * an initial password known to whoever created the account would stay
 * valid indefinitely.
 */
class RequirePasswordChange
{
    /**
     * Routes the user must still be able to reach while pinned.
     */
    private const ALLOWED = [
        'profile.edit',
        'profile.update',
        'password.update',
        'password.confirm',
        'logout',
        'locale.switch',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('auth.must_change_password')], 403);
        }

        return redirect()
            ->route('profile.edit')
            ->with('warning', __('auth.must_change_password'));
    }
}
