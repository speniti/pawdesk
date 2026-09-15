<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Treatment;
use Database\Seeders\DatabaseSeeder;

test('seeding creates exactly one treatment for the completed appointment', function () {
    $this->seed(DatabaseSeeder::class);

    $completedAppointments = Appointment::where('status', AppointmentStatus::Completed->value)->get();

    expect($completedAppointments)->toHaveCount(1)
        ->and(Treatment::count())->toBe(1)
        ->and(Treatment::first()->appointment_id)->toBe($completedAppointments->first()->id);
});

test('the seeded treatment totals come from the appointment services', function () {
    $this->seed(DatabaseSeeder::class);

    $appointment = Appointment::where('status', AppointmentStatus::Completed->value)->first();

    expect($appointment->treatment->final_price)
        ->toBe((int) $appointment->services->sum(fn ($service): int => (int) $service->pivot->applied_price))
        ->and($appointment->treatment->actual_duration_minutes)
        ->toBe((int) $appointment->services->sum(fn ($service): int => (int) $service->pivot->duration_minutes));
});
