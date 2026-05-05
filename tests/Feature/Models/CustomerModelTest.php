<?php

declare(strict_types=1);

use App\Enums\PreferredChannel;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use App\Models\Treatment;

test('Customer factory creates valid record', function () {
    $customer = Customer::factory()->create();

    expect($customer->first_name)->not->toBeEmpty();
    expect($customer->last_name)->not->toBeEmpty();
    expect($customer->email)->toContain('@');
    expect($customer->phone)->not->toBeEmpty();
    expect($customer->preferred_channel)->toBe(PreferredChannel::Email);
    expect($customer->preferences)->toBeArray();
});

test('Customer belongs to tenant', function () {
    $customer = Customer::factory()->create();

    expect($customer->tenant)->toBeInstanceOf(Tenant::class);
});

test('Customer has many pets', function () {
    $customer = Customer::factory()->create();
    Pet::factory()->count(2)->create(['customer_id' => $customer->id, 'tenant_id' => $customer->tenant_id]);

    expect($customer->pets)->toHaveCount(2);
    expect($customer->pets->first())->toBeInstanceOf(Pet::class);
});

test('Customer has many appointments', function () {
    $customer = Customer::factory()->create();
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $customer->tenant_id]);

    Appointment::factory()->count(2)->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $customer->tenant_id,
    ]);

    expect($customer->appointments)->toHaveCount(2);
    expect($customer->appointments->first())->toBeInstanceOf(Appointment::class);
});

test('Customer has many treatments', function () {
    $customer = Customer::factory()->create();
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $customer->tenant_id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $customer->tenant_id,
    ]);

    Treatment::factory()->count(2)->forAppointment($appointment)->create();

    expect($customer->treatments)->toHaveCount(2);
    expect($customer->treatments->first())->toBeInstanceOf(Treatment::class);
});
