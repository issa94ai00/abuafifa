<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\HandlesSuperAdmin;

class ProductPolicy
{
    use HandlesSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ProductView->value);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can(Permission::ProductView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ProductCreate->value);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can(Permission::ProductEdit->value);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can(Permission::ProductDelete->value);
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->can(Permission::ProductRestore->value);
    }

    /**
     * Permanent deletion is destructive and irreversible, so it is kept
     * for super admins only (handled by the before() short-circuit).
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }
}
