<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Models\Concerns\LogsActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * `is_admin` is deliberately absent: back-office membership is a
     * privilege, never something a request body may set. It is assigned
     * with grantStaffAccess()/revokeStaffAccess() only.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'locale',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * User changes are logged explicitly by UserController, which knows
     * the role context (who granted what to whom) that a bare model
     * event cannot see. Mapping them here as well would double every row.
     *
     * @return array<string, ActivityAction>
     */
    protected static function activityActions(): array
    {
        return [];
    }

    /**
     * Login bookkeeping must not produce an audit row per sign-in — the
     * dedicated auth.login event already covers that.
     *
     * @return list<string>
     */
    protected function auditExcept(): array
    {
        return [
            'password', 'remember_token', 'created_at', 'updated_at',
            'last_login_at', 'last_login_ip', 'password_changed_at',
        ];
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function uploadedMedia(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest('created_at');
    }

    // ------------------------------------------------------------ access

    public function isStaff(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin->value);
    }

    /**
     * Whether the user can reach the back office.
     *
     * All three conditions are required: the staff flag, an active
     * account, and at least one recognised back-office role.
     */
    public function canAccessAdmin(): bool
    {
        return $this->isStaff()
            && $this->is_active
            && $this->hasAnyRole(UserRole::values());
    }

    /**
     * The privilege weight of this user's highest role.
     *
     * Used to block privilege escalation: an actor may never create,
     * modify or grant a role at or above their own level (super admins
     * excepted, see UserPolicy).
     */
    public function roleLevel(): int
    {
        return $this->roles
            ->pluck('name')
            ->map(fn (string $name) => UserRole::tryLevel($name))
            ->max() ?? 0;
    }

    /**
     * True when this user outranks the given one.
     */
    public function outranks(self $other): bool
    {
        return $this->roleLevel() > $other->roleLevel();
    }

    /**
     * Grant back-office membership. The only supported way to set the
     * staff flag — it bypasses mass assignment on purpose.
     */
    public function grantStaffAccess(): void
    {
        $this->forceFill(['is_admin' => true])->save();
    }

    public function revokeStaffAccess(): void
    {
        $this->forceFill(['is_admin' => false])->save();
        $this->syncRoles([]);
    }

    /**
     * Normalise the identifier used during authentication (email or phone).
     */
    public static function resolveCredential(string $identifier): ?self
    {
        return static::query()
            ->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();
    }

    /**
     * The default guard used by spatie/laravel-permission.
     */
    protected function getDefaultGuardName(): string
    {
        return 'web';
    }

    public function recordLogin(?string $ip = null): void
    {
        $this->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->saveQuietly();
    }

    public function recordPasswordChange(): void
    {
        $this->forceFill([
            'password_changed_at' => now(),
            'must_change_password' => false,
        ])->saveQuietly();
    }
}
