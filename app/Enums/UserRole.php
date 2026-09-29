<?php

namespace App\Enums;

/**
 * Central list of role names used across the application.
 *
 * Roles are ordered by privilege level; the level is what prevents
 * privilege escalation (a user may never grant or touch a role whose
 * level is greater than or equal to their own — see UserPolicy).
 */
enum UserRole: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case ContentManager = 'content-manager';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'مدير عام',
            self::Admin => 'مشرف',
            self::ContentManager => 'مدير محتوى',
        };
    }

    /**
     * Privilege weight. Higher wins.
     */
    public function level(): int
    {
        return match ($this) {
            self::SuperAdmin => 100,
            self::Admin => 50,
            self::ContentManager => 10,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function tryLevel(?string $name): int
    {
        return self::tryFrom((string) $name)?->level() ?? 0;
    }
}
