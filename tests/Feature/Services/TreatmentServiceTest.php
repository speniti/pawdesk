<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Treatment;
use App\Services\AppointmentPriceCalculator;
use App\Services\TreatmentService;

function attachServices(Appointment $appointment, Service ...$services): void
{
    $pivotData = AppointmentPriceCalculator::buildPivotData(collect($services)->values(), $appointment->pet);
    $appointment->services()->sync($pivotData);
}

test('generateFromAppointment creates a treatment prepopulated from the appointment', function () {
    $appointment = Appointment::factory()->create();
    $service1 = Service::factory()->create([
        'tenant_id' => $appointment->tenant_id,
        'base_price' => 3500,
        'duration_minutes' => 60,
    ]);
    $service2 = Service::factory()->create([
        'tenant_id' => $appointment->tenant_id,
        'base_price' => 5000,
        'duration_minutes' => 45,
    ]);
    attachServices($appointment, $service1, $service2);

    $treatment = (new TreatmentService)->generateFromAppointment($appointment);

    expect($treatment)
        ->appointment_id->toBe($appointment->id)
        ->tenant_id->toBe($appointment->tenant_id)
        ->customer_id->toBe($appointment->customer_id)
        ->pet_id->toBe($appointment->pet_id)
        ->actual_duration_minutes->toBe(105)
        ->final_price->toBe(8500)
        ->visible_to_customer->toBeTrue()
        ->notes->toBeNull()
        ->products_used->toBeNull();
});

test('generateFromAppointment with no services stores zero duration and price', function () {
    $appointment = Appointment::factory()->create();

    $treatment = (new TreatmentService)->generateFromAppointment($appointment);

    expect($treatment)
        ->actual_duration_minutes->toBe(0)
        ->final_price->toBe(0);
});

test('generateFromAppointment is idempotent', function () {
    $appointment = Appointment::factory()->create();
    $service = Service::factory()->create([
        'tenant_id' => $appointment->tenant_id,
        'base_price' => 3500,
        'duration_minutes' => 60,
    ]);
    attachServices($appointment, $service);

    $treatmentService = new TreatmentService;
    $first = $treatmentService->generateFromAppointment($appointment);
    $second = $treatmentService->generateFromAppointment($appointment);

    expect($second->id)->toBe($first->id)
        ->and(Treatment::count())->toBe(1);
});

test('generateFromAppointment does not overwrite an existing treatment', function () {
    $appointment = Appointment::factory()->create();
    $service = Service::factory()->create([
        'tenant_id' => $appointment->tenant_id,
        'base_price' => 3500,
        'duration_minutes' => 60,
    ]);
    attachServices($appointment, $service);

    $treatment = Treatment::factory()->forAppointment($appointment)->create([
        'actual_duration_minutes' => 999,
        'final_price' => 9999,
        'notes' => 'note modificate dallo staff',
    ]);

    $result = (new TreatmentService)->generateFromAppointment($appointment);

    expect($result->id)->toBe($treatment->id)
        ->and($result->refresh())
        ->actual_duration_minutes->toBe(999)
        ->final_price->toBe(9999)
        ->notes->toBe('note modificate dallo staff');
});

test('refreshTotals fills the totals after the services pivot is synced', function () {
    // Scenario #80: the appointment landed on Completed before any service
    // existed, so the observer generated the treatment with zero totals.
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Completed]);
    expect($appointment->treatment->final_price)->toBe(0);

    $service = Service::factory()->create([
        'tenant_id' => $appointment->tenant_id,
        'base_price' => 3500,
        'duration_minutes' => 60,
    ]);
    attachServices($appointment, $service);

    (new TreatmentService)->refreshTotals($appointment);

    expect($appointment->refresh()->treatment)
        ->actual_duration_minutes->toBe(60)
        ->final_price->toBe(3500);
});

test('refreshTotals updates only the totals and keeps the staff fields', function () {
    // Requested: no observer generation, so the factory treatment is the only one.
    $appointment = Appointment::factory()->create();
    $service = Service::factory()->create([
        'tenant_id' => $appointment->tenant_id,
        'base_price' => 3500,
        'duration_minutes' => 60,
    ]);

    $treatment = Treatment::factory()->forAppointment($appointment)->create([
        'actual_duration_minutes' => 999,
        'final_price' => 9999,
        'notes' => 'note modificate dallo staff',
        'visible_to_customer' => false,
    ]);
    attachServices($appointment, $service);

    (new TreatmentService)->refreshTotals($appointment);

    expect($treatment->refresh())
        ->actual_duration_minutes->toBe(60)
        ->final_price->toBe(3500)
        ->notes->toBe('note modificate dallo staff')
        ->visible_to_customer->toBeFalse();
});

test('refreshTotals does nothing when no treatment exists yet', function () {
    $appointment = Appointment::factory()->create();
    $service = Service::factory()->create([
        'tenant_id' => $appointment->tenant_id,
        'base_price' => 3500,
        'duration_minutes' => 60,
    ]);
    attachServices($appointment, $service);

    (new TreatmentService)->refreshTotals($appointment);

    expect(Treatment::count())->toBe(0);
});
