<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Enums\Coat;
use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\Widgets\AppointmentCalendar;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();

    $this->otherTenant = Tenant::factory()->create();

    $otherCustomer = Customer::factory()->create(['tenant_id' => $this->otherTenant->id]);

    $this->foreignPet = Pet::factory()->create([
        'customer_id' => $otherCustomer->id,
        'tenant_id' => $this->otherTenant->id,
        'name' => 'CaneDiUnAltroTenant',
    ]);

    $this->foreignService = Service::factory()->create([
        'tenant_id' => $this->otherTenant->id,
        'name' => 'ServizioDiUnAltroTenant',
    ]);

    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->pet = Pet::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $this->ownService = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'ServizioNostro',
    ]);

    $this->appointment = Appointment::factory()->create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $customer->id,
        'pet_id' => $this->pet->id,
    ]);
});

/**
 * Boots the panel as the current tenant, then deactivates the implicit
 * panel tenancy global scope. What remains in force is only the explicit
 * tenant scoping the app owns — the guarantee these tests pin, simulating
 * the same lookups running outside panel context (queue, console) or a
 * future change in Filament's scoping mechanics.
 */
function bootPanelWithoutImplicitTenancyScope(User $admin, Tenant $tenant): void
{
    bootFilamentPanelAs($admin, $tenant);

    Filament::setCurrentPanel(null);
}

test('scope deactivation harness actually disables the implicit tenancy scope', function () {
    bootPanelWithoutImplicitTenancyScope($this->admin, $this->tenant);

    // If a Filament upgrade ever changes how the tenancy scope activates,
    // this sentinel fails and flags that the isolation drivers below may
    // be passing vacuously.
    expect(Pet::find($this->foreignPet->id))->not->toBeNull();
});

test('appointment form pet lookup ignores foreign pets without the panel tenancy scope', function () {
    bootPanelWithoutImplicitTenancyScope($this->admin, $this->tenant);

    $petFor = Closure::bind(
        fn ($petId) => AppointmentForm::pet($petId),
        null,
        AppointmentForm::class,
    );

    expect($petFor($this->foreignPet->id))->toBeNull()
        ->and($petFor($this->pet->id)?->id)->toBe($this->pet->id);
});

test('appointment form lookups fail closed without a tenant', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);
    Filament::setTenant(null);

    $petFor = Closure::bind(
        fn ($petId) => AppointmentForm::pet($petId),
        null,
        AppointmentForm::class,
    );

    $summarize = Closure::bind(
        fn ($serviceIds, $petId) => AppointmentForm::summarizeServices($serviceIds, $petId),
        null,
        AppointmentForm::class,
    );

    expect($petFor($this->pet->id))->toBeNull()
        ->and($summarize([$this->ownService->id], null))->toBe('—');
});

test('price summary ignores foreign services without the panel tenancy scope', function () {
    bootPanelWithoutImplicitTenancyScope($this->admin, $this->tenant);

    $summarize = Closure::bind(
        fn ($serviceIds, $petId) => AppointmentForm::summarizeServices($serviceIds, $petId),
        null,
        AppointmentForm::class,
    );

    expect($summarize([$this->foreignService->id], null))->toBe('—')
        ->and($summarize([$this->ownService->id], $this->pet->id))->toContain('ServizioNostro')
        ->and($summarize([$this->ownService->id], $this->pet->id))->toContain('Totale');
});

test('service pruning keeps only compatible own-tenant services without the panel tenancy scope', function () {
    $shortCoatService = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'ServizioCorto',
        'coat' => Coat::Short->value,
    ]);

    $longCoatService = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'ServizioLungo',
        'coat' => Coat::Long->value,
    ]);

    bootPanelWithoutImplicitTenancyScope($this->admin, $this->tenant);

    $compatibleIds = Closure::bind(
        fn ($serviceIds, $coat) => AppointmentForm::compatibleServiceIds($serviceIds, $coat),
        null,
        AppointmentForm::class,
    );

    $input = [$this->ownService->id, $shortCoatService->id, $longCoatService->id, $this->foreignService->id];

    expect($compatibleIds($input, Coat::Short))
        ->toBe([$this->ownService->id, $shortCoatService->id])
        ->and($compatibleIds($input, null))->toBe($input);
});

test('service sync ignores foreign services without the panel tenancy scope', function () {
    bootPanelWithoutImplicitTenancyScope($this->admin, $this->tenant);

    $widget = Livewire::test(AppointmentCalendar::class)->instance();
    assert($widget instanceof AppointmentCalendar);

    $syncServices = Closure::bind(
        fn (AppointmentCalendar $calendar, Appointment $appointment, array $serviceIds) => $calendar->syncServicesWithPivotData($appointment, $serviceIds),
        null,
        AppointmentCalendar::class,
    );

    $syncServices($widget, $this->appointment, [$this->foreignService->id]);

    expect(DB::table('appointment_service')
        ->where('appointment_id', $this->appointment->id)
        ->where('service_id', $this->foreignService->id)
        ->exists())->toBeFalse();
});

test('end time calculation ignores foreign services without the panel tenancy scope', function () {
    bootPanelWithoutImplicitTenancyScope($this->admin, $this->tenant);

    $widget = Livewire::test(AppointmentCalendar::class)->instance();
    assert($widget instanceof AppointmentCalendar);

    $calculateEndTime = Closure::bind(
        fn (AppointmentCalendar $calendar, string $startTime, array $serviceIds) => $calendar->calculateEndTime($startTime, $serviceIds, null),
        null,
        AppointmentCalendar::class,
    );

    $start = now()->addDay()->setTime(10, 0)->toIso8601String();

    $end = $calculateEndTime($widget, $start, [$this->foreignService->id]);

    expect($end->equalTo(Carbon::parse($start)))->toBeTrue();
});

test('drag and drop update skips foreign appointments', function () {
    $foreignAppointment = Appointment::factory()->create([
        'tenant_id' => $this->otherTenant->id,
        'customer_id' => $this->foreignPet->customer_id,
        'pet_id' => $this->foreignPet->id,
    ]);
    $originalStart = $foreignAppointment->start_time;
    $originalEnd = $foreignAppointment->end_time;

    bootFilamentPanelAs($this->admin, $this->tenant);

    $widget = Livewire::test(AppointmentCalendar::class)->instance();
    assert($widget instanceof AppointmentCalendar);

    $updateRecord = Closure::bind(
        fn (AppointmentCalendar $calendar, $record) => $calendar->updateRecord(
            $record,
            now()->addDay(),
            now()->addDay()->addHour(),
            false,
        ),
        null,
        AppointmentCalendar::class,
    );

    $updateRecord($widget, $foreignAppointment);

    $stillForeign = Appointment::withoutGlobalScopes()->findOrFail($foreignAppointment->id);

    expect($stillForeign->start_time->equalTo($originalStart))->toBeTrue()
        ->and($stillForeign->end_time->equalTo($originalEnd))->toBeTrue();
});

test('status transitions skip foreign appointments but transition own records', function () {
    $foreignAppointment = Appointment::factory()->create([
        'tenant_id' => $this->otherTenant->id,
        'customer_id' => $this->foreignPet->customer_id,
        'pet_id' => $this->foreignPet->id,
        'status' => AppointmentStatus::Requested,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $widget = Livewire::test(AppointmentCalendar::class)->instance();
    assert($widget instanceof AppointmentCalendar);

    $transitionStatus = Closure::bind(
        fn (AppointmentCalendar $calendar, Appointment $record, AppointmentStatus $nextStatus) => $calendar->transitionStatus($record, $nextStatus),
        null,
        AppointmentCalendar::class,
    );

    $transitionStatus($widget, $foreignAppointment, AppointmentStatus::Confirmed);
    $transitionStatus($widget, $this->appointment, AppointmentStatus::Confirmed);

    expect(Appointment::withoutGlobalScopes()->findOrFail($foreignAppointment->id)->status)
        ->toBe(AppointmentStatus::Requested)
        ->and($this->appointment->refresh()->status)->toBe(AppointmentStatus::Confirmed);
});
