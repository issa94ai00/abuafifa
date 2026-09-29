<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'              => fake()->unique()->name('ar'),
            'email'             => fake()->unique()->safeEmail(),
            'phone'             => fake()->unique()->numerify('+962-7#-###-####'),
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'locale'            => 'ar',
            'is_active'         => true,
            'is_admin'          => false,
            'remember_token'    => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->admin()->state(fn () => [
            'name'  => 'أحمد أبو عفيفة',
            'email' => 'admin@abuafifa.com',
            'phone' => '+962-79-012-3456',
        ]);
    }

    public function password(string $password): static
    {
        return $this->state(fn () => [
            'password' => Hash::make($password),
        ]);
    }
}
