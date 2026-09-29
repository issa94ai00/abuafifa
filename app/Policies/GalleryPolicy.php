<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Gallery;
use App\Models\User;
use App\Policies\Concerns\HandlesSuperAdmin;

class GalleryPolicy
{
    use HandlesSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::GalleryView->value);
    }

    public function view(User $user, Gallery $gallery): bool
    {
        return $user->can(Permission::GalleryView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::GalleryManage->value);
    }

    public function update(User $user, Gallery $gallery): bool
    {
        return $user->can(Permission::GalleryManage->value);
    }

    public function delete(User $user, Gallery $gallery): bool
    {
        return $user->can(Permission::GalleryManage->value);
    }

    public function restore(User $user, Gallery $gallery): bool
    {
        return $user->can(Permission::GalleryManage->value);
    }

    public function forceDelete(User $user, Gallery $gallery): bool
    {
        return false;
    }
}
