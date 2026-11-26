<?php

declare(strict_types=1);

use App\Filament\Resources\Pets\Pages\CreatePet;
use App\Filament\Resources\Pets\Pages\EditPet;
use App\Filament\Resources\Pets\Pages\ListPets;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();

    $this->staff = User::factory()->forTenant($this->tenant)->create();

    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->pet = Pet::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);
});

test('user with access can view pet list', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(ListPets::class)->assertOk();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('user with access can view create pet page', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(CreatePet::class)->assertOk();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('user with access can view edit pet page', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(EditPet::class, ['record' => $this->pet->id])->assertOk();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('staff cannot update or delete pets of another tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $otherCustomer = Customer::factory()->create(['tenant_id' => $otherTenant->id]);
    $foreignPet = Pet::factory()->create([
        'customer_id' => $otherCustomer->id,
        'tenant_id' => $otherTenant->id,
    ]);

    actingAs($this->staff);
    bootFilamentPanel($this->tenant);

    // The foreign record genuinely exists outside the current tenancy scope.
    expect(Pet::withoutGlobalScopes()->findOrFail($foreignPet->id))
        ->tenant_id->toBe($otherTenant->id);

    expect($this->staff->can('view', $foreignPet))->toBeFalse()
        ->and($this->staff->can('update', $foreignPet))->toBeFalse()
        ->and($this->staff->can('delete', $foreignPet))->toBeFalse();
});
