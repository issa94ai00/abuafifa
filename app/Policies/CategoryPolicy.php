<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\HandlesSuperAdmin;

class CategoryPolicy
{
    use HandlesSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::CategoryView->value);
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can(Permission::CategoryView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CategoryCreate->value);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can(Permission::CategoryEdit->value);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can(Permission::CategoryDelete->value);
    }

    public function restore(User $user, Category $category): bool
    {
        return $user->can(Permission::CategoryEdit->value);
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return false;
    }
}
