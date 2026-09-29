<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates the back-office accounts.
 *
 * Passwords are read from the environment. When one is not set a random
 * password is generated and printed once — a seeded password committed
 * to the repository would otherwise be a live credential on every
 * deployment that ran this file.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Seeding would otherwise write a "user created" row per account
        // with no actor behind it.
        ActivityLogger::withoutLogging(function () {
            $this->seedStaff(
                email: env('SEED_SUPER_ADMIN_EMAIL', 'admin@damatech.sy'),
                name: 'أحمد أبو عفيفة',
                phone: '+962790123456',
                role: UserRole::SuperAdmin,
                passwordEnvKey: 'SEED_SUPER_ADMIN_PASSWORD',
            );

            $this->seedStaff(
                email: 'manager@abuafifa.com',
                name: 'مدير الصالة',
                phone: '+962780111222',
                role: UserRole::Admin,
                passwordEnvKey: 'SEED_ADMIN_PASSWORD',
            );

            $this->seedStaff(
                email: 'content@abuafifa.com',
                name: 'مسؤول المحتوى',
                phone: '+962770333444',
                role: UserRole::ContentManager,
                passwordEnvKey: 'SEED_CONTENT_MANAGER_PASSWORD',
            );
        });
    }

    private function seedStaff(
        string $email,
        string $name,
        string $phone,
        UserRole $role,
        string $passwordEnvKey,
    ): void {
        $existing = User::query()->where('email', $email)->first();

        // Never reset the password of an account that already exists —
        // re-running the seeder on a live database must not hand back a
        // known credential.
        if ($existing) {
            $existing->syncRoles([$role->value]);
            $existing->grantStaffAccess();

            return;
        }

        $password = env($passwordEnvKey);
        $generated = false;

        if (blank($password)) {
            $password = Str::password(16);
            $generated = true;
        }

        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
            'locale' => 'ar',
            'is_active' => true,
            'is_admin' => true,
            'email_verified_at' => now(),
            'password_changed_at' => now(),
            // Whoever runs the seeder sees the password once; the holder
            // of the account has to replace it at first sign-in.
            'must_change_password' => true,
        ])->save();

        $user->syncRoles([$role->value]);

        if ($generated) {
            $this->command?->warn("Generated password for {$email}: {$password}");
            $this->command?->warn('Store it now — it is not shown again and must be changed at first login.');
        }
    }
}
