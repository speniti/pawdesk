<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Coat;
use App\Enums\Sex;
use App\Enums\Size;
use App\Enums\Species;
use App\Models\Customer;
use App\Models\Pet;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Pet> */
class PetFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterMaking(function (Pet $pet) {
            if ($pet->tenant_id !== null || $pet->customer_id === null) {
                return;
            }

            $pet->tenant_id = $pet->customer->tenant_id;
        });
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => fake()->firstName(),
            'species' => Species::Dog->value,
            'breed' => 'Meticcio',
            'sex' => fake()->randomElement([Sex::M, Sex::F])->value,
            'date_of_birth' => fake()->dateTimeBetween('-15 years', '-6 months'),
            'size' => Size::Medium->value,
            'coat' => Coat::Short->value,
        ];
    }
}
