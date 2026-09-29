<?php

namespace App\Listeners;

use App\Enums\ActivityAction;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;

/**
 * Turns framework authentication events into audit rows.
 *
 * Hooking the events (rather than the controllers) means every code path
 * that authenticates — controller, console command, test — is covered.
 */
class AuthEventSubscriber
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            $user->recordLogin(request()?->ip());
        }

        $this->logger->log(
            ActivityAction::Login,
            $user instanceof User ? $user : null,
            ['guard' => $event->guard, 'remember' => $event->remember],
            actor: $user instanceof User ? $user : null,
        );
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->logger->log(
            ActivityAction::Logout,
            $event->user,
            ['guard' => $event->guard],
            actor: $event->user,
        );
    }

    /**
     * A failed attempt records the identifier that was tried, never the
     * password — the credentials array is dropped entirely.
     */
    public function handleFailed(Failed $event): void
    {
        $this->logger->log(
            ActivityAction::LoginFailed,
            $event->user instanceof User ? $event->user : null,
            [
                'identifier' => $this->identifierFrom($event->credentials),
                'guard' => $event->guard,
                'account_exists' => $event->user !== null,
            ],
            description: 'محاولة تسجيل دخول فاشلة',
            actor: $event->user instanceof User ? $event->user : null,
        );
    }

    public function handleLockout(Lockout $event): void
    {
        $this->logger->log(
            ActivityAction::Lockout,
            null,
            ['identifier' => $this->identifierFrom($event->request->only('email'))],
            description: 'تم حظر المحاولات مؤقتاً بعد تكرار الفشل',
        );
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->logger->log(
            ActivityAction::PasswordReset,
            $event->user instanceof User ? $event->user : null,
            [],
            actor: $event->user instanceof User ? $event->user : null,
        );
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }

    /**
     * Pull just the login identifier out of a credentials array.
     */
    private function identifierFrom(array $credentials): ?string
    {
        foreach (['email', 'phone', 'username', 'login'] as $key) {
            if (! empty($credentials[$key]) && is_string($credentials[$key])) {
                return mb_substr($credentials[$key], 0, 190);
            }
        }

        return null;
    }
}
