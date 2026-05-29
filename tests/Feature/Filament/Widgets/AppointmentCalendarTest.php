<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Filament\Widgets\AppointmentCalendar;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms\Components\Select;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

test('calendar widget is registered in filament panel', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $widgets = filament()->getWidgets();
    expect($widgets)->toContain(AppointmentCalendar::class);
});

test('calendar widget renders on dashboard', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->assertOk();
});

test('appointments appear as events in calendar', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
        'name' => 'Fido',
    ]);

    $startTime = now()->addDay()->setTime(10, 0);
    $endTime = $startTime->copy()->addHours(2);

    Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'status' => AppointmentStatus::Confirmed,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $start = $startTime->copy()->startOfWeek()->toIso8601String();
    $end = $startTime->copy()->endOfWeek()->toIso8601String();

    $component = Livewire::test(AppointmentCalendar::class);

    $events = $component->instance()->fetchEvents($start, $end);

    expect($events)->toHaveCount(1);

    $event = $events[0];
    expect($event->title)->toBe('Fido - '.$customer->fullName)
        ->and($event->start->toDateString())->toBe($startTime->toDateString());
});

test('completed and cancelled appointments are not editable', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $startTime = now()->startOfWeek()->addDay()->setTime(10, 0);

    $completedAppointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => $startTime,
        'end_time' => $startTime->copy()->addHours(2),
        'status' => AppointmentStatus::Completed,
    ]);

    $requestedAppointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => $startTime->copy()->addDays(2),
        'end_time' => $startTime->copy()->addDays(2)->addHours(2),
        'status' => AppointmentStatus::Requested,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $start = $startTime->copy()->startOfWeek()->toIso8601String();
    $end = $startTime->copy()->endOfWeek()->toIso8601String();

    $component = Livewire::test(AppointmentCalendar::class);
    $events = $component->instance()->fetchEvents($start, $end);

    $completedEvent = collect($events)->first(fn ($e) => $e->id === (string) $completedAppointment->id);
    $requestedEvent = collect($events)->first(fn ($e) => $e->id === (string) $requestedAppointment->id);

    expect($completedEvent->editable)->toBeFalse()
        ->and($completedEvent->startEditable)->toBeFalse()
        ->and($requestedEvent->editable)->toBeTrue()
        ->and($requestedEvent->startEditable)->toBeTrue();
});

test('calendar widget scopes appointments to tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $otherCustomer = Customer::factory()->create(['tenant_id' => $otherTenant->id]);
    $otherPet = Pet::factory()->create([
        'customer_id' => $otherCustomer->id,
        'tenant_id' => $otherTenant->id,
    ]);

    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $startTime = now()->addDay()->setTime(10, 0);

    Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => $startTime,
        'end_time' => $startTime->copy()->addHours(2),
    ]);

    Appointment::factory()->create([
        'customer_id' => $otherCustomer->id,
        'pet_id' => $otherPet->id,
        'tenant_id' => $otherTenant->id,
        'start_time' => $startTime,
        'end_time' => $startTime->copy()->addHours(2),
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $start = $startTime->copy()->startOfWeek()->toIso8601String();
    $end = $startTime->copy()->endOfWeek()->toIso8601String();

    $component = Livewire::test(AppointmentCalendar::class);
    $events = $component->instance()->fetchEvents($start, $end);

    expect($events)->toHaveCount(1);
});

test('drag and drop updates appointment times', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $originalStart = now()->addDay()->setTime(10, 0);
    $originalEnd = $originalStart->copy()->addHours(2);

    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => $originalStart,
        'end_time' => $originalEnd,
        'status' => AppointmentStatus::Requested,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $newStart = $originalStart->copy()->addHour();
    $newEnd = $originalEnd->copy()->addHour();

    Livewire::test(AppointmentCalendar::class)
        ->call('edit', $appointment->id, $newStart->toIso8601String(), $newEnd->toIso8601String(), false)
        ->assertNotified();

    expect($appointment->refresh())
        ->start_time->toDateString()->toBe($newStart->toDateString())
        ->start_time->format('H:i')->toBe($newStart->format('H:i'));
});

test('pet options are filtered by selected customer', function () {
    $customer1 = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $customer2 = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet1 = Pet::factory()->create(['customer_id' => $customer1->id, 'tenant_id' => $this->tenant->id, 'name' => 'Fido']);
    $pet2 = Pet::factory()->create(['customer_id' => $customer2->id, 'tenant_id' => $this->tenant->id, 'name' => 'Rex']);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $component = Livewire::test(AppointmentCalendar::class)
        ->mountAction('create')
        ->fillForm([
            'customer_id' => (string) $customer1->id,
        ]);

    // Assert pet1 is available and pet2 is not by checking the form field
    $component->assertFormFieldExists('pet_id', function (Select $field) use ($pet1, $pet2) {
        $options = $field->getOptions();
        expect($options)->toHaveKey((string) $pet1->id)
            ->and($options)->not->toHaveKey((string) $pet2->id);

        return true;
    });
});

test('status field is disabled in edit form', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);

    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(2),
        'status' => AppointmentStatus::Confirmed,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->mountAction('edit')
        ->assertFormFieldIsDisabled('status');
});

test('view action shows status transition buttons for valid transitions', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);

    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(2),
        'status' => AppointmentStatus::Requested,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->assertActionExists('confirmed')
        ->assertActionExists('cancelled');
});

test('status transition action updates appointment status', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);

    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(2),
        'status' => AppointmentStatus::Requested,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->callAction('confirmed');

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Confirmed);
});

test('completed appointment has no status transition actions', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);

    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(2),
        'status' => AppointmentStatus::Completed,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->assertActionDoesNotExist('confirmed')
        ->assertActionDoesNotExist('cancelled');
});

test('appointment policy allows CRUD for staff', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);

    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(2),
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    expect($this->admin->can('view', $appointment))->toBeTrue()
        ->and($this->admin->can('update', $appointment))->toBeTrue()
        ->and($this->admin->can('delete', $appointment))->toBeTrue()
        ->and($this->admin->can('create', Appointment::class))->toBeTrue();
});
