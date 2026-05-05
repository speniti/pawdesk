<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Treatment> */
class TreatmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'actual_duration_minutes' => fake()->numberBetween(15, 120),
            'final_price' => fake()->numberBetween(1500, 10000),
            'notes' => fake()->optional()->sentence(),
            'visible_to_customer' => true,
            'products_used' => fake()->optional()->sentence(),
        ];
    }

    public function forAppointment(Appointment $appointment): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $appointment->tenant_id,
            'appointment_id' => $appointment->id,
            'customer_id' => $appointment->customer_id,
            'pet_id' => $appointment->pet_id,
        ]);
    }
}
