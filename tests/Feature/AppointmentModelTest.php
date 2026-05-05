<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Models\User;

test('Appointment has correct fillable attributes', function () {
    $appointment = new Appointment;

    expect($appointment->getFillable())->toBe([
        'tenant_id',
        'customer_id',
        'pet_id',
        'user_id',
        'status',
        'start_time',
        'end_time',
        'internal_notes',
    ]);
});

test('Appointment casts attributes correctly', function () {
    $appointment = new Appointment;
    $casts = $appointment->getCasts();

    expect($casts)->toHaveKey('status', AppointmentStatus::class);
    expect($casts)->toHaveKey('start_time', 'datetime');
    expect($casts)->toHaveKey('end_time', 'datetime');
});

test('Appointment factory creates valid record', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->status)->toBe(AppointmentStatus::Requested);
    expect($appointment->start_time)->not->toBeNull();
    expect($appointment->end_time)->not->toBeNull();
});

test('Appointment belongs to tenant', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->tenant)->toBeInstanceOf(Tenant::class);
});

test('Appointment belongs to customer', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->customer)->toBeInstanceOf(Customer::class);
});

test('Appointment belongs to pet', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->pet)->toBeInstanceOf(Pet::class);
});

test('Appointment belongs to user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $tenant->id]);

    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'user_id' => $user->id,
    ]);

    expect($appointment->user)->toBeInstanceOf(User::class);
});

test('Appointment belongs to many services with pivot', function () {
    $appointment = Appointment::factory()->create();
    $service = Service::factory()->create(['tenant_id' => $appointment->tenant_id]);

    $appointment->services()->attach($service->id, [
        'applied_price' => 4500,
        'duration_minutes' => 60,
    ]);

    expect($appointment->services)->toHaveCount(1);
    expect($appointment->services->first())->toBeInstanceOf(Service::class);
    expect($appointment->services->first()->pivot->applied_price)->toBe(4500);
    expect($appointment->services->first()->pivot->duration_minutes)->toBe(60);
});

test('Appointment has one treatment', function () {
    $appointment = Appointment::factory()->create();

    Treatment::factory()->forAppointment($appointment)->create();

    expect($appointment->treatment)->toBeInstanceOf(Treatment::class);
});
