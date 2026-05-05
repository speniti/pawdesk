<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Sex;
use App\Enums\Size;
use App\Enums\Species;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Pet> */
class PetFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'tenant_id' => fn (array $attributes) => Customer::find($attributes['customer_id'])?->tenant_id ?? Customer::factory()->create()->tenant_id,
            'name' => fake()->firstName(),
            'species' => Species::Dog->value,
            'sex' => Sex::Unknown->value,
            'size' => Size::Medium->value,
        ];
    }
}
