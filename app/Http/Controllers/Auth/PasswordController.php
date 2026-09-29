<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // `current_password` re-proves the identity of whoever is at
            // the keyboard, so a hijacked session cannot change it.
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
            'must_change_password' => false,
        ])->save();

        // Invalidate the session on every other device. Requires the
        // AuthenticateSession middleware, which the web routes apply.
        Auth::logoutOtherDevices($validated['password']);

        $request->session()->regenerate();

        $this->logger->log(ActivityAction::PasswordChanged, $user, [], actor: $user);

        return back()->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }
}
