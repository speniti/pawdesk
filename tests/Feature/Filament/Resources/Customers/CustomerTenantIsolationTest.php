<?php

declare(strict_types=1);

use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();

    $this->otherTenant = Tenant::factory()->create();

    $this->foreignCustomer = Customer::factory()->create([
        'tenant_id' => $this->otherTenant->id,
        'first_name' => 'Cliente',
        'last_name' => 'DiUnAltroTenant',
        'email' => 'cliente.di-un-altro-tenant@example.com',
    ]);

    $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
});

test('customer list is scoped to the current tenant', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    // The foreign customer genuinely exists outside the tenancy scope.
    expect(Customer::withoutGlobalScopes()->findOrFail($this->foreignCustomer->id))
        ->tenant_id->toBe($this->otherTenant->id);

    Livewire::test(ListCustomers::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->customer])
        ->assertCanNotSeeTableRecords([$this->foreignCustomer])
        ->assertDontSee('Cliente DiUnAltroTenant')
        ->assertDontSee('cliente.di-un-altro-tenant@example.com');
});

test('customer view page returns 404 for a foreign tenant customer', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ViewCustomer::class, ['record' => $this->foreignCustomer->id])
        ->assertStatus(404);
});

test('customer edit page returns 404 for a foreign tenant customer', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditCustomer::class, ['record' => $this->foreignCustomer->id])
        ->assertStatus(404);

    // The foreign record exists and is untouched.
    expect(Customer::withoutGlobalScopes()->findOrFail($this->foreignCustomer->id))
        ->email->toBe('cliente.di-un-altro-tenant@example.com');
});
