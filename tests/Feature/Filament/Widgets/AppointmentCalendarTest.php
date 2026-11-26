<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Enums\Size;
use App\Filament\Widgets\AppointmentCalendar;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Models\User;
use App\Notifications\AppointmentConfirmedNotification;
use App\Services\AppointmentPriceCalculator;
use Filament\Forms\Components\Select;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();
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

test('status field is editable in edit form', function () {
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
        ->assertFormFieldExists('status', fn (Select $field) => ! $field->isDisabled());
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

test('creating appointment syncs services with pivot data', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id, 'size' => Size::Small]);
    $service = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'base_price' => 5000,
        'duration_minutes' => 60,
        'variations' => [
            ['size' => 'small', 'coat' => 'short', 'price' => 3500, 'duration_minutes' => 75],
        ],
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);
    $start = now()->addDay()->setTime(10, 0);

    $component = Livewire::test(AppointmentCalendar::class)
        ->mountAction('create')
        ->fillForm([
            'customer_id' => (string) $customer->id,
            'pet_id' => (string) $pet->id,
            'start_time' => $start->format('Y-m-d H:i'),
            'services' => [(string) $service->id],
            'status' => AppointmentStatus::Requested->value,
        ])
        ->callMountedAction();

    $appointment = Appointment::latest()->first();
    $pivot = $appointment->services()->first()->pivot;

    expect($pivot->applied_price)->toBe(3500)
        ->and($pivot->duration_minutes)->toBe(75);
});

test('pivot uses base price and duration when no combination matches', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id, 'size' => Size::Large]);
    $service = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'base_price' => 5000,
        'duration_minutes' => 45,
        'variations' => [
            ['size' => 'large', 'coat' => 'long', 'price' => 3500, 'duration_minutes' => 90],
        ],
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);
    $start = now()->addDay()->setTime(10, 0);

    Livewire::test(AppointmentCalendar::class)
        ->mountAction('create')
        ->fillForm([
            'customer_id' => (string) $customer->id,
            'pet_id' => (string) $pet->id,
            'start_time' => $start->format('Y-m-d H:i'),
            'services' => [(string) $service->id],
            'status' => AppointmentStatus::Requested->value,
        ])
        ->callMountedAction();

    $appointment = Appointment::latest()->first();
    $pivot = $appointment->services()->first()->pivot;

    expect($pivot->applied_price)->toBe(5000)
        ->and($pivot->duration_minutes)->toBe(45);
});

test('end_time is auto-calculated from services duration on create', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $service1 = Service::factory()->create(['tenant_id' => $this->tenant->id, 'duration_minutes' => 30]);
    $service2 = Service::factory()->create(['tenant_id' => $this->tenant->id, 'duration_minutes' => 45]);

    bootFilamentPanelAs($this->admin, $this->tenant);
    $start = now()->addDay()->setTime(10, 0);

    Livewire::test(AppointmentCalendar::class)
        ->mountAction('create')
        ->fillForm([
            'customer_id' => (string) $customer->id,
            'pet_id' => (string) $pet->id,
            'start_time' => $start->format('Y-m-d H:i'),
            'services' => [(string) $service1->id, (string) $service2->id],
            'status' => AppointmentStatus::Requested->value,
        ])
        ->callMountedAction();

    $appointment = Appointment::latest()->first();
    expect($appointment->end_time->format('H:i'))->toBe('11:15');
});

test('edit completing the appointment generates treatment totals from the new services', function () {
    Notification::fake();

    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(11, 0),
        'status' => AppointmentStatus::InProgress,
    ]);
    $oldService = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'base_price' => 2000,
        'duration_minutes' => 30,
    ]);
    $newService = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'base_price' => 5000,
        'duration_minutes' => 60,
    ]);
    $appointment->services()->sync(
        AppointmentPriceCalculator::buildPivotData(
            Service::where('id', $oldService->id)->get(),
            $pet,
        ),
    );

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->mountAction('edit')
        ->fillForm([
            'services' => [(string) $newService->id],
            'status' => AppointmentStatus::Completed->value,
        ])
        ->callMountedAction();

    $treatment = $appointment->treatment()->first();

    expect($treatment)->not->toBeNull()
        ->actual_duration_minutes->toBe(60)
        ->final_price->toBe(5000);
});

test('illegal status transition via edit form is rejected', function () {
    Notification::fake();

    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(11, 0),
        'status' => AppointmentStatus::Cancelled,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->mountAction('edit')
        ->fillForm(['status' => AppointmentStatus::Completed->value])
        ->callMountedAction()
        ->assertHasFormErrors(['status' => 'Lo stato Completato non è raggiungibile da Annullato.']);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and(Treatment::count())->toBe(0);

    Notification::assertNothingSent();
});

test('legal status transition via edit form is allowed', function () {
    Notification::fake();

    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(11, 0),
        'status' => AppointmentStatus::Requested,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->mountAction('edit')
        ->fillForm(['status' => AppointmentStatus::Confirmed->value])
        ->callMountedAction();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Confirmed);

    Notification::assertSentTo($customer, AppointmentConfirmedNotification::class);
});

test('saving the edit form without changing the status still works', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(11, 0),
        'status' => AppointmentStatus::Requested,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->mountAction('edit')
        ->fillForm(['internal_notes' => 'note aggiornate'])
        ->callMountedAction();

    expect($appointment->refresh())
        ->internal_notes->toBe('note aggiornate')
        ->status->toBe(AppointmentStatus::Requested)
        ->and(Treatment::count())->toBe(0);
});

test('creating an appointment with a non-initial status is rejected', function (AppointmentStatus $status) {
    Notification::fake();

    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $service = Service::factory()->create(['tenant_id' => $this->tenant->id, 'duration_minutes' => 60]);

    bootFilamentPanelAs($this->admin, $this->tenant);
    $start = now()->addDay()->setTime(10, 0);

    Livewire::test(AppointmentCalendar::class)
        ->mountAction('create')
        ->fillForm([
            'customer_id' => (string) $customer->id,
            'pet_id' => (string) $pet->id,
            'start_time' => $start->format('Y-m-d H:i'),
            'services' => [(string) $service->id],
            'status' => $status->value,
        ])
        ->callMountedAction()
        ->assertHasFormErrors(['status' => 'Lo stato selezionato non è ammesso alla creazione di un appuntamento.']);

    expect(Appointment::count())->toBe(0)
        ->and(Treatment::count())->toBe(0);

    Notification::assertNothingSent();
})->with([
    'confirmed' => AppointmentStatus::Confirmed,
    'in progress' => AppointmentStatus::InProgress,
    'completed' => AppointmentStatus::Completed,
    'cancelled' => AppointmentStatus::Cancelled,
    'no show' => AppointmentStatus::NoShow,
]);

test('create form only offers initial statuses', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->mountAction('create')
        ->assertFormFieldExists('status', function (Select $field) {
            expect($field->getOptions())->toBe([
                AppointmentStatus::Requested->value => AppointmentStatus::Requested->getLabel(),
            ]);

            return true;
        });
});

test('edit form only offers the current and reachable statuses', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(11, 0),
        'status' => AppointmentStatus::Confirmed,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->mountAction('edit')
        ->assertFormFieldExists('status', function (Select $field) {
            expect($field->getOptions())->toBe([
                AppointmentStatus::Cancelled->value => AppointmentStatus::Cancelled->getLabel(),
                AppointmentStatus::Confirmed->value => AppointmentStatus::Confirmed->getLabel(),
                AppointmentStatus::InProgress->value => AppointmentStatus::InProgress->getLabel(),
                AppointmentStatus::NoShow->value => AppointmentStatus::NoShow->getLabel(),
            ]);

            return true;
        });
});

test('edit form for a terminal status only offers the current status and still saves', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(11, 0),
        'status' => AppointmentStatus::Completed,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $component = Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->mountAction('edit');

    $component->assertFormFieldExists('status', function (Select $field) {
        expect($field->getOptions())->toBe([
            AppointmentStatus::Completed->value => AppointmentStatus::Completed->getLabel(),
        ]);

        return true;
    });

    $component
        ->fillForm(['internal_notes' => 'note su appuntamento completato'])
        ->callMountedAction();

    expect($appointment->refresh())
        ->internal_notes->toBe('note su appuntamento completato')
        ->status->toBe(AppointmentStatus::Completed);
});

test('edit form rejects a status that is not a valid case', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(11, 0),
        'status' => AppointmentStatus::Requested,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->mountAction('edit')
        ->fillForm(['status' => 'not_a_real_status'])
        ->callMountedAction()
        ->assertHasFormErrors(['status']);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Requested)
        ->and(Treatment::count())->toBe(0);
});

test('the save guard halts illegal transitions without writing', function () {
    Notification::fake();

    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(11, 0),
        'status' => AppointmentStatus::Cancelled,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $component = Livewire::test(AppointmentCalendar::class);

    $guard = Closure::bind(
        fn (AppointmentCalendar $calendar, Appointment $record, mixed $status) => $calendar->assertLegalStatusTransition($record, $status),
        null,
        AppointmentCalendar::class,
    );

    try {
        $guard($component->instance(), $appointment, AppointmentStatus::Completed->value);
        $this->fail('The guard should have halted the illegal transition.');
    } catch (Halt) {
        // Expected: the save path stops before any write.
    }

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and(Treatment::count())->toBe(0);

    $component->assertNotified('Transizione non consentita');
});

test('the save guard passes unchanged and legal transitions without notifying', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $requested = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(11, 0),
        'status' => AppointmentStatus::Requested,
    ]);
    $cancelled = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(12, 0),
        'end_time' => now()->addDay()->setTime(13, 0),
        'status' => AppointmentStatus::Cancelled,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $component = Livewire::test(AppointmentCalendar::class);

    $guard = Closure::bind(
        fn (AppointmentCalendar $calendar, Appointment $record, mixed $status) => $calendar->assertLegalStatusTransition($record, $status),
        null,
        AppointmentCalendar::class,
    );

    $guard($component->instance(), $cancelled, AppointmentStatus::Cancelled->value);
    $guard($component->instance(), $cancelled, AppointmentStatus::Cancelled);
    $guard($component->instance(), $requested, AppointmentStatus::Confirmed->value);

    $component->assertNotNotified();
});

test('edit save rejects a transition made illegal by a concurrent update', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(11, 0),
        'status' => AppointmentStatus::Requested,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $originalNotes = $appointment->internal_notes;

    $component = Livewire::test(AppointmentCalendar::class)
        ->call('select', $appointment->id)
        ->mountAction('edit')
        ->fillForm([
            'status' => AppointmentStatus::Confirmed->value,
            'internal_notes' => 'note che non devono essere salvate',
        ]);

    // Simulate a concurrent transition after the form was filled. Livewire
    // rehydrates the record from the database on the submitting request, so
    // the validation layer already sees the persisted status and rejects
    // the now-illegal transition before the save path is reached.
    Appointment::query()->whereKey($appointment->id)->update([
        'status' => AppointmentStatus::Cancelled->value,
    ]);

    $component
        ->callMountedAction()
        ->assertHasFormErrors(['status' => 'Lo stato Confermato non è raggiungibile da Annullato.']);

    expect($appointment->refresh())
        ->status->toBe(AppointmentStatus::Cancelled)
        ->internal_notes->toBe($originalNotes)
        ->and(Treatment::count())->toBe(0);
});

test('transition status action re-verifies against the persisted record', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create(['customer_id' => $customer->id, 'tenant_id' => $this->tenant->id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
        'tenant_id' => $this->tenant->id,
        'start_time' => now()->addDay()->setTime(10, 0),
        'end_time' => now()->addDay()->setTime(11, 0),
        'status' => AppointmentStatus::Requested,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    // Stale in-memory status: the row already moved on to a terminal state.
    Appointment::query()->whereKey($appointment->id)->update([
        'status' => AppointmentStatus::Cancelled->value,
    ]);

    $widget = Livewire::test(AppointmentCalendar::class);

    $transitionStatus = Closure::bind(
        fn (AppointmentCalendar $calendar, Appointment $record, AppointmentStatus $nextStatus) => $calendar->transitionStatus($record, $nextStatus),
        null,
        AppointmentCalendar::class,
    );

    $transitionStatus($widget->instance(), $appointment, AppointmentStatus::Confirmed);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});
