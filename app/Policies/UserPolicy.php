<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\User;

/**
 * User management is the highest-value target in the panel: whoever can
 * edit users can grant themselves anything. The rules here are therefore
 * stricter than elsewhere and the super-admin short-circuit is *not*
 * blanket — self-demotion and self-deletion stay blocked for everyone.
 */
class UserPolicy
{
    /**
     * Abilities that no one — super admin included — may exercise on
     * themselves, because doing so can lock the system out.
     */
    private const SELF_PROTECTED = ['delete', 'toggleActive', 'assignRoles'];

    public function before(User $user, string $ability, mixed ...$arguments): ?bool
    {
        if (! $user->canAccessAdmin()) {
            return false;
        }

        $target = $arguments[0] ?? null;

        // Self-protected abilities fall through to the explicit method so
        // the "not on yourself" rule still gets a say.
        if ($target instanceof User && $target->is($user) && in_array($ability, self::SELF_PROTECTED, true)) {
            return null;
        }

        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::UserView->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(Permission::UserView->value) && $this->mayTouch($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::UserCreate->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(Permission::UserEdit->value) && $this->mayTouch($user, $model);
    }

    /**
     * Deleting your own account is never allowed — it would strand the
     * session and can remove the last super admin.
     */
    public function delete(User $user, User $model): bool
    {
        if ($model->is($user)) {
            return false;
        }

        if (! $user->hasRole(UserRole::SuperAdmin->value) && ! $user->can(Permission::UserDelete->value)) {
            return false;
        }

        return $this->mayTouch($user, $model) && ! $this->isLastSuperAdmin($model);
    }

    /**
     * Flipping the active flag on your own account locks you out.
     */
    public function toggleActive(User $user, User $model): bool
    {
        if ($model->is($user)) {
            return false;
        }

        return $user->can(Permission::UserEdit->value) && $this->mayTouch($user, $model);
    }

    /**
     * Role assignment is the privilege-escalation choke point.
     *
     * An actor may only hand out roles strictly below their own level,
     * may not change the roles of a peer or superior, and may never
     * change their own roles (a super admin who could would be able to
     * accidentally demote the last super admin).
     *
     * @param  list<string>  $roles  role names being assigned
     */
    public function assignRoles(User $user, User $model, array $roles = []): bool
    {
        if ($model->is($user)) {
            return false;
        }

        if (! $user->can(Permission::RoleManage->value) && ! $user->hasRole(UserRole::SuperAdmin->value)) {
            return false;
        }

        if (! $this->mayTouch($user, $model)) {
            return false;
        }

        return $this->mayGrant($user, $roles);
    }

    /**
     * Whether every requested role sits below the actor's own level.
     *
     * @param  list<string>  $roles
     */
    public function mayGrant(User $user, array $roles): bool
    {
        // Super admins may grant any role except creating another super
        // admin implicitly — that is allowed, but only for them.
        $ceiling = $user->hasRole(UserRole::SuperAdmin->value)
            ? UserRole::SuperAdmin->level()
            : $user->roleLevel();

        foreach ($roles as $role) {
            $level = UserRole::tryLevel(is_string($role) ? $role : (string) $role);

            // Unknown role names are refused outright.
            if ($level === 0) {
                return false;
            }

            // Non-super-admins must stay strictly below their own level.
            if (! $user->hasRole(UserRole::SuperAdmin->value) && $level >= $ceiling) {
                return false;
            }

            if ($level > $ceiling) {
                return false;
            }
        }

        return true;
    }

    public function viewActivity(User $user, User $model): bool
    {
        return $user->can(Permission::ActivityView->value);
    }

    // ------------------------------------------------------------ helpers

    /**
     * An actor may only act on users they outrank. Equal-level accounts
     * are off limits, which stops one admin from hijacking another.
     */
    private function mayTouch(User $user, User $model): bool
    {
        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return true;
        }

        return $user->outranks($model);
    }

    /**
     * Refuse to remove the final super admin.
     */
    private function isLastSuperAdmin(User $model): bool
    {
        if (! $model->hasRole(UserRole::SuperAdmin->value)) {
            return false;
        }

        return User::role(UserRole::SuperAdmin->value)->count() <= 1;
    }
}
