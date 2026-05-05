<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Appointment> */
class AppointmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => Customer::factory(),
            'pet_id' => Pet::factory(),
            'status' => AppointmentStatus::Requested->value,
            'start_time' => now()->addDays(fake()->numberBetween(1, 30)),
            'end_time' => fn (array $attributes) => Carbon::parse($attributes['start_time'])->addHours(2),
            'internal_notes' => fake()->optional()->sentence(),
        ];
    }
}
