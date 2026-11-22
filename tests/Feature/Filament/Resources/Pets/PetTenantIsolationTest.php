<?php

declare(strict_types=1);

use App\Filament\Resources\Pets\Pages\EditPet;
use App\Filament\Resources\Pets\Pages\ListPets;
use App\Filament\Resources\Pets\Pages\ViewPet;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);

    $this->otherTenant = Tenant::factory()->create();

    $foreignCustomer = Customer::factory()->create(['tenant_id' => $this->otherTenant->id]);
    $this->foreignPet = Pet::factory()->create([
        'customer_id' => $foreignCustomer->id,
        'tenant_id' => $this->otherTenant->id,
        'name' => 'CaneDiUnAltroTenant',
    ]);

    $ownCustomer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->pet = Pet::factory()->create([
        'customer_id' => $ownCustomer->id,
        'tenant_id' => $this->tenant->id,
        'name' => 'Fido',
    ]);
});

test('pet list is scoped to the current tenant', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    // The foreign pet genuinely exists outside the tenancy scope.
    expect(Pet::withoutGlobalScopes()->findOrFail($this->foreignPet->id))
        ->tenant_id->toBe($this->otherTenant->id);

    Livewire::test(ListPets::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->pet])
        ->assertCanNotSeeTableRecords([$this->foreignPet])
        ->assertDontSee('CaneDiUnAltroTenant');
});

test('pet view page returns 404 for a foreign tenant pet', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ViewPet::class, ['record' => $this->foreignPet->id])
        ->assertStatus(404);
});

test('pet edit page returns 404 for a foreign tenant pet', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditPet::class, ['record' => $this->foreignPet->id])
        ->assertStatus(404);

    // The foreign record exists and is untouched.
    expect(Pet::withoutGlobalScopes()->findOrFail($this->foreignPet->id))
        ->name->toBe('CaneDiUnAltroTenant');
});
