<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ContactMessage;
use App\Models\User;
use App\Policies\Concerns\HandlesSuperAdmin;

class ContactMessagePolicy
{
    use HandlesSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ContactView->value);
    }

    public function view(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can(Permission::ContactView->value);
    }

    public function create(User $user): bool
    {
        // Messages only ever arrive from the public contact form.
        return false;
    }

    public function update(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can(Permission::ContactManage->value);
    }

    public function delete(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can(Permission::ContactManage->value);
    }
}
