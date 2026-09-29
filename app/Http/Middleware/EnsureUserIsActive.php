<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Lock out deactivated accounts on their very next request, rather
     * than waiting for the session to lapse.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();

            // Without invalidating, the old session id keeps its payload.
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => __('auth.deactivated')], 403)
                : redirect()->route('login')->withErrors(['email' => __('auth.deactivated')]);
        }

        return $next($request);
    }
}
