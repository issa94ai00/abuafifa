<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Media;
use App\Models\User;
use App\Policies\Concerns\HandlesSuperAdmin;

class MediaPolicy
{
    use HandlesSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MediaUpload->value)
            || $user->can(Permission::MediaDelete->value);
    }

    public function view(User $user, Media $media): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Uploading is the 'create' ability; the alias keeps both spellings
     * working for callers.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::MediaUpload->value);
    }

    public function upload(User $user): bool
    {
        return $this->create($user);
    }

    public function update(User $user, Media $media): bool
    {
        return $user->can(Permission::MediaUpload->value);
    }

    public function delete(User $user, Media $media): bool
    {
        return $user->can(Permission::MediaDelete->value);
    }
}
