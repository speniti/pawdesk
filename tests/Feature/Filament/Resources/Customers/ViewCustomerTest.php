<?php

declare(strict_types=1);

use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\PetsRelationManager;
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
