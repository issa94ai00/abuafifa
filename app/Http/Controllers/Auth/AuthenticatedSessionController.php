<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * The Login event fired by the guard is what writes the audit row and
     * stamps last_login_at — see AuthEventSubscriber.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Rotate the session id so a token captured before sign-in is
        // useless afterwards (session fixation).
        $request->session()->regenerate();

        return redirect()->intended($this->redirectPathFor($request->user()));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Read the user before logging out so the Logout event — and the
        // audit row it produces — still knows who left.
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Staff land in the back office, customers on the storefront.
     */
    private function redirectPathFor(?User $user): string
    {
        if ($user?->canAccessAdmin()) {
            return route('admin.dashboard', absolute: false);
        }

        return route('home', absolute: false);
    }
}
