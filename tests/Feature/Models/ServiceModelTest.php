<?php

declare(strict_types=1);

use App\Enums\ServiceStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;

test('Service factory creates valid record', function () {
    $service = Service::factory()->create();

    expect($service->name)->not->toBeEmpty();
    expect($service->duration_minutes)->toBeInt();
    expect($service->base_price)->toBeInt();
    expect($service->combinable)->toBeTrue();
    expect($service->status)->toBe(ServiceStatus::Active);
    expect($service->size_prices)->toBeArray();
});

test('Service belongs to tenant', function () {
    $service = Service::factory()->create();

    expect($service->tenant)->toBeInstanceOf(Tenant::class);
});

test('Service belongs to many appointments', function () {
    $service = Service::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $service->tenant_id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $service->tenant_id]);

    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $service->tenant_id,
    ]);

    $appointment->services()->attach($service->id, [
        'applied_price' => 3000,
        'duration_minutes' => 45,
    ]);

    expect($service->appointments)->toHaveCount(1);
    expect($service->appointments->first()->pivot->applied_price)->toBe(3000);
    expect($service->appointments->first()->pivot->duration_minutes)->toBe(45);
});
