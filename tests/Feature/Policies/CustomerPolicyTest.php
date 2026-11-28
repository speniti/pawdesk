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

    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();

    $this->staff = User::factory()->forTenant($this->tenant)->create();

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

test('staff cannot update or delete customers of another tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $foreignCustomer = Customer::factory()->create(['tenant_id' => $otherTenant->id]);

    actingAs($this->staff);
    bootFilamentPanel($this->tenant);

    // The foreign record genuinely exists outside the current tenancy scope.
    expect(Customer::withoutGlobalScopes()->findOrFail($foreignCustomer->id))
        ->tenant_id->toBe($otherTenant->id);

    expect($this->staff->can('view', $foreignCustomer))->toBeFalse()
        ->and($this->staff->can('update', $foreignCustomer))->toBeFalse()
        ->and($this->staff->can('delete', $foreignCustomer))->toBeFalse();
});

test('a non-member gains no abilities on another tenant panel', function (User $outsider) {
    actingAs($outsider);
    bootFilamentPanel($this->tenant);

    // The outsider has the right role but no membership in the booted
    // tenant: the policy must deny every ability on its own (#92), not
    // lean on the panel middleware having already kept them out.
    expect($outsider->can('viewAny', Customer::class))->toBeFalse()
        ->and($outsider->can('create', Customer::class))->toBeFalse()
        ->and($outsider->can('view', $this->customer))->toBeFalse()
        ->and($outsider->can('update', $this->customer))->toBeFalse()
        ->and($outsider->can('delete', $this->customer))->toBeFalse()
        ->and($outsider->can('restore', $this->customer))->toBeFalse()
        ->and($outsider->can('forceDelete', $this->customer))->toBeFalse();
})->with([
    'admin outsider' => fn () => User::factory()->admin()->forTenant(Tenant::factory()->create())->create(),
    'staff outsider' => fn () => User::factory()->forTenant(Tenant::factory()->create())->create(),
]);
