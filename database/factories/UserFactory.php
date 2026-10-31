<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 * Used by tests only. Demo/sample data is never created from factories in production.
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'role_id' => fn () => Role::forSlug(RoleSlug::Client)->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function role(RoleSlug $role): static
    {
        return $this->state(fn () => ['role_id' => Role::forSlug($role)->id]);
    }

    public function superAdmin(): static
    {
        return $this->role(RoleSlug::SuperAdmin);
    }

    public function agency(): static
    {
        return $this->role(RoleSlug::Agency);
    }

    public function vendor(): static
    {
        return $this->role(RoleSlug::Vendor);
    }

    public function status(UserStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
