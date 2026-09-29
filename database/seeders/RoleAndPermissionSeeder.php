<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The single source of truth for what each role may do.
 *
 * Re-running this seeder is safe and is the supported way to change a
 * role's permissions: syncPermissions() removes anything not listed, so
 * a permission granted by hand in the database is revoked on the next
 * run rather than lingering unnoticed.
 */
class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::query()->updateOrCreate(
                ['name' => $permission->value, 'guard_name' => 'web'],
            );
        }

        foreach ($this->definitions() as $roleName => $permissions) {
            $role = Role::query()->updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
            );

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<string, list<string>>
     */
    private function definitions(): array
    {
        return [
            // Full access, including users, roles and system settings.
            UserRole::SuperAdmin->value => Permission::values(),

            // Runs the shop day to day: the whole catalogue and the
            // inbox, but no users, no roles and no system settings.
            UserRole::Admin->value => [
                Permission::DashboardView->value,

                Permission::ProductView->value,
                Permission::ProductCreate->value,
                Permission::ProductEdit->value,
                Permission::ProductDelete->value,
                Permission::ProductRestore->value,

                Permission::CategoryView->value,
                Permission::CategoryCreate->value,
                Permission::CategoryEdit->value,
                Permission::CategoryDelete->value,

                Permission::OfferView->value,
                Permission::OfferCreate->value,
                Permission::OfferEdit->value,
                Permission::OfferDelete->value,

                Permission::GalleryView->value,
                Permission::GalleryManage->value,

                Permission::MediaUpload->value,
                Permission::MediaDelete->value,

                Permission::ContactView->value,
                Permission::ContactManage->value,

                Permission::ActivityView->value,
            ],

            // Produces content. May add and edit, but deleting published
            // records, reading the inbox in full, touching users or
            // changing settings are all out of reach.
            UserRole::ContentManager->value => [
                Permission::DashboardView->value,

                Permission::ProductView->value,
                Permission::ProductCreate->value,
                Permission::ProductEdit->value,

                Permission::CategoryView->value,
                Permission::CategoryCreate->value,
                Permission::CategoryEdit->value,

                Permission::OfferView->value,
                Permission::OfferCreate->value,
                Permission::OfferEdit->value,

                Permission::GalleryView->value,
                Permission::GalleryManage->value,

                Permission::MediaUpload->value,

                Permission::ContactView->value,
            ],
        ];
    }
}
