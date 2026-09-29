<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Offer;
use App\Models\User;
use App\Policies\Concerns\HandlesSuperAdmin;

class OfferPolicy
{
    use HandlesSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::OfferView->value);
    }

    public function view(User $user, Offer $offer): bool
    {
        return $user->can(Permission::OfferView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::OfferCreate->value);
    }

    public function update(User $user, Offer $offer): bool
    {
        return $user->can(Permission::OfferEdit->value);
    }

    public function delete(User $user, Offer $offer): bool
    {
        return $user->can(Permission::OfferDelete->value);
    }

    public function restore(User $user, Offer $offer): bool
    {
        return $user->can(Permission::OfferEdit->value);
    }

    public function forceDelete(User $user, Offer $offer): bool
    {
        return false;
    }
}
