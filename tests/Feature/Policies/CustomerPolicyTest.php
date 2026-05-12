<?php

declare(strict_types=1);

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);

    $this->staff = User::factory()->create();
    $this->staff->tenants()->attach($this->tenant);

    $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
});

test('user with access can view customer list', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(ListCustomers::class)->assertOk();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('user with access can view create customer page', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(CreateCustomer::class)->assertOk();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('user with access can view edit customer page', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(EditCustomer::class, ['record' => $this->customer->id])->assertOk();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);
