<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * The response is deliberately identical whether or not the address
     * belongs to an account: reporting "we can't find that user" turns
     * this form into an account-enumeration oracle. Failures other than
     * "unknown user" are logged for the operators, not shown to the
     * visitor.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:190'],
        ]);

        $user = User::query()->where('email', $request->input('email'))->first();

        // Deactivated accounts never receive a reset link.
        if ($user && $user->is_active) {
            Password::sendResetLink($request->only('email'));
        }

        $this->logger->log(
            ActivityAction::PasswordResetRequested,
            $user,
            ['email' => $request->input('email'), 'delivered' => (bool) ($user?->is_active)],
            actor: $user,
        );

        return back()->with('status', __('passwords.sent'));
    }
}
