<?php

declare(strict_types=1);

use App\Enums\Sex;
use App\Enums\Size;
use App\Enums\Species;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use App\Models\Treatment;

test('Pet factory creates valid record', function () {
    $pet = Pet::factory()->create();

    expect($pet->name)->not->toBeEmpty();
    expect($pet->species)->toBe(Species::Dog);
    expect($pet->size)->toBe(Size::Medium);
    expect($pet->sex)->toBe(Sex::Unknown);
});

test('Pet belongs to tenant', function () {
    $pet = Pet::factory()->create();

    expect($pet->tenant)->toBeInstanceOf(Tenant::class);
});

test('Pet belongs to customer', function () {
    $pet = Pet::factory()->create();

    expect($pet->customer)->toBeInstanceOf(Customer::class);
});

test('Pet has many appointments', function () {
    $pet = Pet::factory()->create();

    Appointment::factory()->count(2)->create([
        'customer_id' => $pet->customer_id,
        'pet_id' => $pet->id,
        'tenant_id' => $pet->tenant_id,
    ]);

    expect($pet->appointments)->toHaveCount(2);
    expect($pet->appointments->first())->toBeInstanceOf(Appointment::class);
});

test('Pet has many treatments', function () {
    $pet = Pet::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $pet->customer_id,
        'pet_id' => $pet->id,
        'tenant_id' => $pet->tenant_id,
    ]);

    Treatment::factory()->count(2)->forAppointment($appointment)->create();

    expect($pet->treatments)->toHaveCount(2);
    expect($pet->treatments->first())->toBeInstanceOf(Treatment::class);
});
