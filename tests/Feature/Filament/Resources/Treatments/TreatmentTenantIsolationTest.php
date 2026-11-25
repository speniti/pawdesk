<?php

declare(strict_types=1);

use App\Filament\Resources\Treatments\Pages\EditTreatment;
use App\Filament\Resources\Treatments\Pages\ListTreatments;
use App\Filament\Resources\Treatments\Pages\ViewTreatment;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);

    $this->otherTenant = Tenant::factory()->create();

    $foreignCustomer = Customer::factory()->create(['tenant_id' => $this->otherTenant->id]);
    $foreignPet = Pet::factory()->create([
        'customer_id' => $foreignCustomer->id,
        'tenant_id' => $this->otherTenant->id,
        'name' => 'CaneDiUnAltroTenant',
    ]);

    $foreignAppointment = Appointment::factory()->create([
        'tenant_id' => $this->otherTenant->id,
        'customer_id' => $foreignCustomer->id,
        'pet_id' => $foreignPet->id,
    ]);

    $this->foreignTreatment = Treatment::factory()->forAppointment($foreignAppointment)->create([
        'final_price' => 999999,
    ]);

    $ownAppointment = Appointment::factory()->forTenant($this->tenant)->create();
    $this->treatment = Treatment::factory()->forAppointment($ownAppointment)->create();
});

test('treatment list is scoped to the current tenant', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    // The foreign treatment genuinely exists outside the tenancy scope.
    expect(Treatment::withoutGlobalScopes()->findOrFail($this->foreignTreatment->id))
        ->tenant_id->toBe($this->otherTenant->id);

    Livewire::test(ListTreatments::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->treatment])
        ->assertCanNotSeeTableRecords([$this->foreignTreatment])
        ->assertDontSee('CaneDiUnAltroTenant')
        ->assertDontSee('€9,999.99');
});

test('treatment view page returns 404 for a foreign tenant treatment', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ViewTreatment::class, ['record' => $this->foreignTreatment->id])
        ->assertStatus(404);
});

test('treatment edit page returns 404 for a foreign tenant treatment', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditTreatment::class, ['record' => $this->foreignTreatment->id])
        ->assertStatus(404);

    // One-way tripwire for side-effect mutations during the 404 request: the
    // page mount performs no write, and a later Livewire hydrate re-resolves
    // the record through the same tenancy-scoped query, so the foreign price
    // can never be modified through this surface.
    expect(Treatment::withoutGlobalScopes()->findOrFail($this->foreignTreatment->id))
        ->final_price->toBe(999999);
});

test('treatments table registers no bulk delete action', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    // Treatments are system-generated and carry prices, so the table
    // deliberately registers no destructive bulk action: this pins that no
    // crafted bulk request can reach one on this surface. The crafted
    // foreign+own bulk delete path itself is pinned in
    // PetTenantIsolationTest, where a DeleteBulkAction is registered.
    Livewire::test(ListTreatments::class)
        ->assertTableBulkActionDoesNotExist('delete');
});
