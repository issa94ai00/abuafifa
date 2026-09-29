<?php

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Super admins short-circuit every ability check.
 *
 * The exceptions live in UserPolicy, where even a super admin may not
 * delete or demote their own account — that rule keeps the system from
 * ending up with no administrator at all.
 */
trait HandlesSuperAdmin
{
    public function before(User $user, string $ability): ?bool
    {
        // An inactive or non-staff account never passes, whatever its roles.
        if (! $user->canAccessAdmin()) {
            return false;
        }

        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return true;
        }

        return null;
    }
}
