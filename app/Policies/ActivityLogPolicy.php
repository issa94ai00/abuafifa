<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ActivityLog;
use App\Models\User;
use App\Policies\Concerns\HandlesSuperAdmin;

/**
 * The audit trail is read-only by design: there is no update ability,
 * and deletion is a retention operation reserved for super admins.
 */
class ActivityLogPolicy
{
    use HandlesSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ActivityView->value);
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $user->can(Permission::ActivityView->value);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function delete(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    /**
     * Bulk pruning of entries older than the retention window.
     */
    public function purge(User $user): bool
    {
        return $user->can(Permission::ActivityPurge->value);
    }
}
