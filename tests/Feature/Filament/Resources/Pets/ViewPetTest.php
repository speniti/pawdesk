<?php

declare(strict_types=1);

use App\Filament\Resources\Pets\Pages\ViewPet;
use App\Filament\Resources\Pets\RelationManagers\AppointmentsRelationManager;
use App\Filament\Resources\Pets\Widgets\PetStats;
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

test('admin can view pet detail page', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

    $pet = Pet::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ViewPet::class, ['record' => $pet->id])
        ->assertOk();
});

test('view pet page shows pet name as title', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

    $pet = Pet::factory()->create([
        'name' => 'Fido',
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ViewPet::class, ['record' => $pet->id])
        ->assertSee('Fido');
});

test('view pet page renders stats widget in header', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

    $pet = Pet::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(PetStats::class, ['record' => $pet])
        ->assertSee('Spesa totale')
        ->assertSee('Appuntamenti');
});

test('view pet page shows appointments via relation manager', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

    $pet = Pet::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $appointments = Appointment::factory()->count(3)->create([
        'pet_id' => $pet->id,
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(AppointmentsRelationManager::class, [
        'ownerRecord' => $pet,
        'pageClass' => ViewPet::class,
    ])
        ->assertCanSeeTableRecords($appointments);
});
