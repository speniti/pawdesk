<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use App\Models\Treatment;

test('Treatment has correct fillable attributes', function () {
    $treatment = new Treatment;

    expect($treatment->getFillable())->toBe([
        'tenant_id',
        'appointment_id',
        'customer_id',
        'pet_id',
        'actual_duration_minutes',
        'final_price',
        'notes',
        'visible_to_customer',
        'products_used',
    ]);
});

test('Treatment casts attributes correctly', function () {
    $treatment = new Treatment;
    $casts = $treatment->getCasts();

    expect($casts)->toHaveKey('actual_duration_minutes', 'integer');
    expect($casts)->toHaveKey('final_price', 'integer');
    expect($casts)->toHaveKey('visible_to_customer', 'boolean');
});

test('Treatment factory with forAppointment creates valid record', function () {
    $appointment = Appointment::factory()->create();
    $treatment = Treatment::factory()->forAppointment($appointment)->create();

    expect($treatment->tenant_id)->toBe($appointment->tenant_id);
    expect($treatment->appointment_id)->toBe($appointment->id);
    expect($treatment->customer_id)->toBe($appointment->customer_id);
    expect($treatment->pet_id)->toBe($appointment->pet_id);
    expect($treatment->actual_duration_minutes)->toBeInt();
    expect($treatment->visible_to_customer)->toBeTrue();
});

test('Treatment belongs to tenant', function () {
    $appointment = Appointment::factory()->create();
    $treatment = Treatment::factory()->forAppointment($appointment)->create();

    expect($treatment->tenant)->toBeInstanceOf(Tenant::class);
});

test('Treatment belongs to appointment', function () {
    $appointment = Appointment::factory()->create();
    $treatment = Treatment::factory()->forAppointment($appointment)->create();

    expect($treatment->appointment)->toBeInstanceOf(Appointment::class);
    expect($treatment->appointment->id)->toBe($appointment->id);
});

test('Treatment belongs to customer', function () {
    $appointment = Appointment::factory()->create();
    $treatment = Treatment::factory()->forAppointment($appointment)->create();

    expect($treatment->customer)->toBeInstanceOf(Customer::class);
});

test('Treatment belongs to pet', function () {
    $appointment = Appointment::factory()->create();
    $treatment = Treatment::factory()->forAppointment($appointment)->create();

    expect($treatment->pet)->toBeInstanceOf(Pet::class);
});
