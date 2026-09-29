<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Setting;
use App\Models\User;
use App\Policies\Concerns\HandlesSuperAdmin;

class SettingPolicy
{
    use HandlesSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::SettingView->value);
    }

    public function view(User $user, ?Setting $setting = null): bool
    {
        return $user->can(Permission::SettingView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::SettingManage->value);
    }

    public function update(User $user, ?Setting $setting = null): bool
    {
        return $user->can(Permission::SettingManage->value);
    }

    public function delete(User $user, ?Setting $setting = null): bool
    {
        return $user->can(Permission::SettingManage->value);
    }
}
