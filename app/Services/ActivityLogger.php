<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The single writer of the audit trail.
 *
 * Design notes:
 *  - Logging never breaks the request it describes: every failure is
 *    swallowed and forwarded to the application log instead.
 *  - Actor identity is denormalised onto the row so the trail stays
 *    readable after a user is deleted.
 *  - Anything that looks like a secret is redacted before storage.
 */
class ActivityLogger
{
    /**
     * Attribute names that must never reach the audit table.
     *
     * @var list<string>
     */
    private const REDACTED = [
        'password', 'password_confirmation', 'current_password',
        'remember_token', 'token', 'api_token', 'secret',
        'two_factor_secret', 'two_factor_recovery_codes',
        '_token', 'credit_card', 'cvv',
    ];

    /**
     * Attributes that are noise in a diff.
     *
     * @var list<string>
     */
    private const IGNORED = ['updated_at', 'created_at', 'views_count'];

    /**
     * Global kill switch — used by seeders, factories and tests that would
     * otherwise flood the audit table with meaningless rows.
     */
    private static bool $enabled = true;

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function enable(): void
    {
        self::$enabled = true;
    }

    public static function enabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Run a callback with auditing suspended, restoring the previous state.
     */
    public static function withoutLogging(callable $callback): mixed
    {
        $previous = self::$enabled;
        self::$enabled = false;

        try {
            return $callback();
        } finally {
            self::$enabled = $previous;
        }
    }

    public function log(
        ActivityAction $action,
        ?Model $subject = null,
        array $properties = [],
        ?string $description = null,
        ?User $actor = null,
    ): ?ActivityLog {
        if (! self::$enabled) {
            return null;
        }

        try {
            $actor ??= $this->currentActor();
            $request = request();

            // forceCreate, not create: ActivityLog guards every attribute so
            // that this service stays its only writer.
            return ActivityLog::query()->forceCreate(array_filter([
                'user_id' => $actor?->getKey(),
                'user_name' => $actor?->name,
                'user_email' => $actor?->email,
                'user_role' => $actor?->roles->pluck('name')->implode(', ') ?: null,

                'action' => $action->value,
                'action_group' => $action->group(),
                'description' => $description ? Str::limit($description, 490) : $action->label(),

                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject ? $this->labelFor($subject) : null,

                'properties' => $this->sanitize($properties) ?: null,

                'ip_address' => $request?->ip(),
                'user_agent' => Str::limit((string) $request?->userAgent(), 490, ''),
                'method' => $request?->method(),
                'url' => Str::limit((string) $request?->fullUrl(), 490, ''),
                'route_name' => $request?->route()?->getName(),

                'is_security_event' => $action->isSecurityEvent(),
                'created_at' => now(),
            ], static fn ($value) => $value !== null));
        } catch (\Throwable $e) {
            // An audit failure must never take the request down with it.
            Log::error('ActivityLogger failed: '.$e->getMessage(), [
                'action' => $action->value,
                'subject' => $subject ? $subject::class.'#'.$subject->getKey() : null,
            ]);

            return null;
        }
    }

    /**
     * Log a model change, recording only the attributes that actually moved.
     */
    public function logModelChange(ActivityAction $action, Model $subject, array $original = []): ?ActivityLog
    {
        return $this->log($action, $subject, $this->diff($original, $subject->getAttributes()));
    }

    /**
     * Build a before/after diff, dropping unchanged, ignored and secret keys.
     *
     * @return array{changed?: array<string, array{from: mixed, to: mixed}>}
     */
    public function diff(array $before, array $after): array
    {
        $changed = [];

        foreach ($after as $key => $newValue) {
            if (in_array($key, self::IGNORED, true) || $this->isSensitive($key)) {
                continue;
            }

            $oldValue = $before[$key] ?? null;

            if ($this->normalise($oldValue) === $this->normalise($newValue)) {
                continue;
            }

            $changed[$key] = [
                'from' => $this->truncate($oldValue),
                'to' => $this->truncate($newValue),
            ];
        }

        return $changed ? ['changed' => $changed] : [];
    }

    /**
     * Snapshot a model's attributes for a later diff (e.g. before delete).
     */
    public function snapshot(Model $model): array
    {
        return Arr::except($model->getAttributes(), self::REDACTED);
    }

    // ------------------------------------------------------------- helpers

    private function currentActor(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private function sanitize(array $properties): array
    {
        $clean = [];

        foreach ($properties as $key => $value) {
            if ($this->isSensitive((string) $key)) {
                $clean[$key] = '[redacted]';

                continue;
            }

            $clean[$key] = is_array($value) ? $this->sanitize($value) : $this->truncate($value);
        }

        return $clean;
    }

    private function isSensitive(string $key): bool
    {
        $key = Str::lower($key);

        foreach (self::REDACTED as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalise(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        if (is_numeric($value)) {
            return (string) (float) $value;
        }

        return is_scalar($value) || $value === null ? $value : json_encode($value);
    }

    private function truncate(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_string($value) && mb_strlen($value) > 300) {
            return mb_substr($value, 0, 300).'…';
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : class_basename($value);
        }

        return $value;
    }

    private function labelFor(Model $subject): ?string
    {
        foreach (['name_ar', 'title_ar', 'name', 'title', 'email', 'slug', 'subject', 'filename', 'key'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (filled($value) && is_scalar($value)) {
                return Str::limit((string) $value, 180, '');
            }
        }

        return class_basename($subject).' #'.$subject->getKey();
    }
}
