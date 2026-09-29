<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idle and absolute timeouts for back-office sessions.
 *
 * Laravel's session lifetime is a sliding cookie window; this adds a
 * server-side check so an unattended admin session dies on schedule and
 * a stolen session cookie has a bounded useful life.
 */
class EnsureAdminSessionIsFresh
{
    private const LAST_ACTIVITY = 'admin.last_activity_at';

    private const STARTED_AT = 'admin.session_started_at';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $session = $request->session();
        $now = now()->getTimestamp();

        $idleTimeout = (int) config('security.admin_session.idle_timeout') * 60;
        $absoluteTimeout = (int) config('security.admin_session.absolute_timeout') * 60;

        $startedAt = (int) $session->get(self::STARTED_AT, $now);
        $lastActivity = (int) $session->get(self::LAST_ACTIVITY, $now);

        $expiredByIdle = $idleTimeout > 0 && ($now - $lastActivity) > $idleTimeout;
        $expiredByAge = $absoluteTimeout > 0 && ($now - $startedAt) > $absoluteTimeout;

        if ($expiredByIdle || $expiredByAge) {
            return $this->expire($request, $expiredByIdle ? 'idle' : 'absolute');
        }

        $session->put(self::LAST_ACTIVITY, $now);
        $session->put(self::STARTED_AT, $startedAt);

        return $next($request);
    }

    /**
     * Not named `terminate`: the HTTP kernel calls a `terminate()` method on
     * every resolved middleware after the response is sent, which would hit
     * this one with the wrong arguments.
     */
    private function expire(Request $request, string $reason): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $reason === 'idle'
            ? __('auth.session_idle_expired')
            : __('auth.session_expired');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
