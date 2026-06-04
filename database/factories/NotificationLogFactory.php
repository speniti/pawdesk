<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationLog> */
class NotificationLogFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => Customer::factory(),
            'appointment_id' => Appointment::factory(),
            'type' => fake()->word(),
            'channel' => 'mail',
            'status' => NotificationStatus::Pending->value,
        ];
    }

    public function forAppointment(Appointment $appointment): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $appointment->tenant_id,
            'customer_id' => $appointment->customer_id,
            'appointment_id' => $appointment->id,
        ]);
    }
}
