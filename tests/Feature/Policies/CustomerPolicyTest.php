<?php

declare(strict_types=1);

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);

    $this->staff = User::factory()->create();
    $this->staff->tenants()->attach($this->tenant);

    $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
});

test('admin can view customer list', function () {
    bootFilamentTenantAs($this->admin);

    Livewire::test(ListCustomers::class)->assertOk();
});

test('staff can view customer list', function () {
    bootFilamentTenantAs($this->staff);

    Livewire::test(ListCustomers::class)->assertOk();
});

test('admin can view create customer page', function () {
    bootFilamentTenantAs($this->admin);

    Livewire::test(CreateCustomer::class)->assertOk();
});

test('staff can view create customer page', function () {
    bootFilamentTenantAs($this->staff);

    Livewire::test(CreateCustomer::class)->assertOk();
});

test('admin can view edit customer page', function () {
    bootFilamentTenantAs($this->admin);

    Livewire::test(EditCustomer::class, ['record' => $this->customer->id])->assertOk();
});

test('staff can view edit customer page', function () {
    bootFilamentTenantAs($this->staff);

    Livewire::test(EditCustomer::class, ['record' => $this->customer->id])->assertOk();
});
