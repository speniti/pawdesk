<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PreferredChannel;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Customer> */
class CustomerFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'preferred_channel' => PreferredChannel::Email->value,
            'preferences' => [],
        ];
    }
}
