<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Admin]);
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'role' => UserRole::Staff,
        ];
    }

    // Membrazione al momento della creazione (#83): sostituisce il pattern
    // ripetuto "create() + ->tenants()->attach($tenant)" diffuso nei test.
    public function forTenant(Tenant $tenant): static
    {
        return $this->afterCreating(fn (User $user) => $user->tenants()->attach($tenant));
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }
}
