<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Service> */
class ServiceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $services = [
            'Full Groom', 'Bath & Brush', 'Haircut & Styling', 'Nail Trim',
            'Ear Cleaning', 'Teeth Brushing', 'De-shedding Treatment',
            'Flea Bath', 'Puppy First Groom', 'Sanitary Trim',
        ];

        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->randomElement($services),
            'description' => fake()->optional()->sentence(),
            'category' => fake()->randomElement(['grooming', 'bath', 'trimming', 'wellness', 'specialty']),
            'coat' => null,
            'duration_minutes' => fake()->numberBetween(15, 120),
            'base_price' => fake()->numberBetween(1500, 8000),
            'combinable' => true,
            'status' => ServiceStatus::Active->value,
            'size_prices' => [],
        ];
    }
}
