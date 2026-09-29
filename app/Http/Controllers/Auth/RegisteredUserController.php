<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service registration creates *storefront customers only*.
 *
 * Back-office membership (`is_admin`) and roles are never touched here;
 * both are granted deliberately by a super admin. The routes themselves
 * are only registered when auth.registration_enabled is on, and the
 * guard below covers the case where a stale route cache says otherwise.
 */
class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        $this->ensureRegistrationIsEnabled();

        return Inertia::render('Auth/Register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureRegistrationIsEnabled();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Explicit attribute list — never $request->all() — so a crafted
        // body cannot smuggle in is_admin or is_active.
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'locale' => app()->getLocale(),
            'is_active' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('home');
    }

    private function ensureRegistrationIsEnabled(): void
    {
        abort_unless(config('auth.registration_enabled', false), 404);
    }
}
