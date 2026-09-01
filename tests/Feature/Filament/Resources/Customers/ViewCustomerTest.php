<?php

declare(strict_types=1);

use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\PetsRelationManager;
use App\Filament\Resources\Customers\RelationManagers\TreatmentsRelationManager;
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
});

test('admin can view customer detail page', function () {
    $customer = Customer::factory()->create([
        'tenant_id' => $this->tenant->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'email' => 'mario@example.com',
        'phone' => '+39 02 1234567',
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ViewCustomer::class, ['record' => $customer->id])
        ->assertOk();
});

test('view customer page shows linked pets via relation manager', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

    $pets = Pet::factory()->count(3)->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(PetsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => ViewCustomer::class,
    ])
        ->assertCanSeeTableRecords($pets);
});

test('view customer page shows treatments across pets via relation manager', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

    $pets = Pet::factory()->count(2)->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $treatments = $pets->map(fn (Pet $pet): Treatment => Treatment::factory()
        ->forAppointment(Appointment::factory()->create([
            'pet_id' => $pet->id,
            'customer_id' => $customer->id,
            'tenant_id' => $this->tenant->id,
        ]))
        ->create());

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(TreatmentsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => ViewCustomer::class,
    ])
        ->assertCanSeeTableRecords($treatments);
});
