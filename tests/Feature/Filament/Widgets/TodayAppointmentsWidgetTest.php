<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Filament\Widgets\TodayAppointmentsWidget;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

function todayAppointment(Customer $customer, Pet $pet, AppointmentStatus $status): Appointment
{
    return Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $customer->tenant_id,
        'status' => $status,
        'start_time' => now()->setTime(10, 0),
        'end_time' => now()->setTime(11, 0),
    ]);
}

function widgetStats(): array
{
    $widget = Livewire::test(TodayAppointmentsWidget::class)->instance();

    return invade($widget)->getStats();
}

test('counts today appointments per status', function () {
    $customer = Customer::factory()->for($this->tenant)->create();
    $pet = Pet::factory()->for($customer)->for($this->tenant)->create();

    todayAppointment($customer, $pet, AppointmentStatus::Requested);
    todayAppointment($customer, $pet, AppointmentStatus::Requested);
    todayAppointment($customer, $pet, AppointmentStatus::Confirmed);
    todayAppointment($customer, $pet, AppointmentStatus::InProgress);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $stats = collect(widgetStats())->keyBy(fn ($stat) => $stat->getLabel());

    expect($stats['Richiesti']->getValue())->toBe(2)
        ->and($stats['Confermati']->getValue())->toBe(1)
        ->and($stats['In corso']->getValue())->toBe(1);
});

test('ignores appointments from other days or closed statuses', function () {
    $customer = Customer::factory()->for($this->tenant)->create();
    $pet = Pet::factory()->for($customer)->for($this->tenant)->create();

    Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'status' => AppointmentStatus::Completed,
        'start_time' => now()->setTime(9, 0),
        'end_time' => now()->setTime(10, 0),
    ]);

    Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'status' => AppointmentStatus::Requested,
        'start_time' => now()->addDay()->setTime(9, 0),
        'end_time' => now()->addDay()->setTime(10, 0),
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $stats = collect(widgetStats())->keyBy(fn ($stat) => $stat->getLabel());

    expect($stats['Richiesti']->getValue())->toBe(0)
        ->and($stats['Confermati']->getValue())->toBe(0)
        ->and($stats['In corso']->getValue())->toBe(0);
});

test('scopes counts to current tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $otherCustomer = Customer::factory()->for($otherTenant)->create();
    $otherPet = Pet::factory()->for($otherCustomer)->for($otherTenant)->create();

    todayAppointment($otherCustomer, $otherPet, AppointmentStatus::Requested);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $stats = collect(widgetStats())->keyBy(fn ($stat) => $stat->getLabel());

    expect($stats['Richiesti']->getValue())->toBe(0);
});

test('widget renders on dashboard', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(TodayAppointmentsWidget::class)->assertOk();
});
