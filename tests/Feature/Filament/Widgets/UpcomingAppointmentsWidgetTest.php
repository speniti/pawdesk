<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Filament\Widgets\UpcomingAppointmentsWidget;
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

    $this->customer = Customer::factory()->for($this->tenant)->create();
    $this->pet = Pet::factory()->for($this->customer)->for($this->tenant)->create(['name' => 'Fido']);
});

function upcomingAppointment(string $name, int $hoursFromNow, AppointmentStatus $status = AppointmentStatus::Confirmed): Appointment
{
    $pet = Pet::factory()->for(test()->customer)->for(test()->tenant)->create(['name' => $name]);

    return Appointment::factory()->create([
        'customer_id' => test()->customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => test()->tenant->id,
        'status' => $status,
        'start_time' => now()->addHours($hoursFromNow)->setMinutes(0),
        'end_time' => now()->addHours($hoursFromNow)->addHour()->setMinutes(0),
    ]);
}

test('lists next five confirmed appointments ordered by start time', function () {
    foreach ([48, 6, 24, 2, 72, 12] as $i => $hours) {
        upcomingAppointment("Pet{$i}", $hours);
    }

    bootFilamentPanelAs($this->admin, $this->tenant);

    $component = Livewire::test(UpcomingAppointmentsWidget::class);

    expect($component->instance()->getTableRecords()->pluck('pet_id'))
        ->toHaveCount(5);

    $component->assertSeeTextInOrder(['Pet3', 'Pet1', 'Pet5', 'Pet2', 'Pet0']);
});

test('excludes non-confirmed and finished appointments', function () {
    upcomingAppointment('FutureRequested', 3, AppointmentStatus::Requested);
    upcomingAppointment('FutureInProgress', 4, AppointmentStatus::InProgress);
    Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'pet_id' => $this->pet->id,
        'tenant_id' => $this->tenant->id,
        'status' => AppointmentStatus::Confirmed,
        'start_time' => now()->subHours(2),
        'end_time' => now()->subHour(),
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $component = Livewire::test(UpcomingAppointmentsWidget::class);

    expect($component->instance()->getTableRecords())->toBeEmpty();
});

test('includes confirmed appointments already underway', function () {
    Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'pet_id' => $this->pet->id,
        'tenant_id' => $this->tenant->id,
        'status' => AppointmentStatus::Confirmed,
        'start_time' => now()->subHour(),
        'end_time' => now()->addHour(),
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $component = Livewire::test(UpcomingAppointmentsWidget::class);

    expect($component->instance()->getTableRecords())->toHaveCount(1);
});

test('scopes upcoming appointments to current tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $otherCustomer = Customer::factory()->for($otherTenant)->create();
    $otherPet = Pet::factory()->for($otherCustomer)->for($otherTenant)->create();

    Appointment::factory()->create([
        'customer_id' => $otherCustomer->id,
        'pet_id' => $otherPet->id,
        'tenant_id' => $otherTenant->id,
        'status' => AppointmentStatus::Confirmed,
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHour(),
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $component = Livewire::test(UpcomingAppointmentsWidget::class);

    expect($component->instance()->getTableRecords())->toBeEmpty();
});
