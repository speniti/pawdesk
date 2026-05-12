<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Appointment> */
class AppointmentFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterMaking(function (Appointment $appointment) {
            if ($appointment->tenant_id !== null || $appointment->customer_id === null) {
                return;
            }

            $appointment->tenant_id = $appointment->customer->tenant_id;
        });
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'pet_id' => Pet::factory(),
            'status' => AppointmentStatus::Requested->value,
            'start_time' => now()->addDays(fake()->numberBetween(1, 30)),
            'end_time' => fn (array $attributes) => Carbon::parse($attributes['start_time'])->addHours(2),
            'internal_notes' => fake()->optional()->sentence(),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'customer_id' => Customer::factory()->for($tenant),
            'pet_id' => Pet::factory()->for($tenant)->for(Customer::factory()->for($tenant)),
        ]);
    }
}
