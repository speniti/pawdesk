<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Treatment;
use App\Services\AppointmentPriceCalculator;
use App\Services\TreatmentService;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\mock;

beforeEach(function () {
    Notification::fake();
});

test('transition to Completed generates a treatment with pivot totals', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Confirmed->value]);
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
    $appointment->services()->sync(
        AppointmentPriceCalculator::buildPivotData(
            Service::whereIn('id', [$service1->id, $service2->id])->get(),
            $appointment->pet?->size,
        ),
    );

    $appointment->update(['status' => AppointmentStatus::InProgress->value]);
    $appointment->update(['status' => AppointmentStatus::Completed->value]);

    $treatment = $appointment->treatment()->first();

    expect($treatment)->not->toBeNull()
        ->actual_duration_minutes->toBe(105)
        ->final_price->toBe(8500);
});

test('status changes to non-Completed statuses generate no treatment', function (AppointmentStatus $status) {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Confirmed->value]);

    $appointment->update(['status' => $status->value]);

    expect(Treatment::count())->toBe(0);
})->with([
    'in progress' => AppointmentStatus::InProgress,
    'cancelled' => AppointmentStatus::Cancelled,
    'no show' => AppointmentStatus::NoShow,
]);

test('non-status updates generate no treatment', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::InProgress->value]);

    $appointment->update(['internal_notes' => 'some notes']);

    expect(Treatment::count())->toBe(0);
});

test('appointment created directly as Completed generates a treatment', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Completed->value]);

    expect($appointment->treatment)->not->toBeNull()
        ->and(Treatment::count())->toBe(1);
});

test('generation failure rolls back the status change', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::InProgress->value]);

    mock(TreatmentService::class)
        ->shouldReceive('generateFromAppointment')
        ->andThrow(new RuntimeException('generation failed'));

    try {
        $appointment->update(['status' => AppointmentStatus::Completed->value]);
        $this->fail('The update should have failed.');
    } catch (RuntimeException) {
        // Expected: the observer exception aborts the save.
    }

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::InProgress)
        ->and(Treatment::count())->toBe(0);
});
