<?php

declare(strict_types=1);

use App\Filament\Resources\Pets\Pages\CreatePet;
use App\Filament\Resources\Pets\Pages\EditPet;
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

test('admin can create a pet', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(CreatePet::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'name' => 'Buddy',
            'species' => 'dog',
            'breed' => 'Labrador',
            'sex' => 'm',
            'date_of_birth' => '2020-01-15',
            'size' => 'large',
            'coat' => 'short',
        ])
        ->call('create')
        ->assertNotified();

    $pet = Pet::where('name', 'Buddy')->first();

    expect($pet)->not->toBeNull()
        ->and($pet->species->value)->toBe('dog')
        ->and($pet->customer_id)->toBe($customer->id)
        ->and($pet->customer->first_name)->toBe($customer->first_name);
});

test('admin can update a pet', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditPet::class, ['record' => $pet->id])
        ->fillForm([
            'customer_id' => $pet->customer_id,
            'name' => 'Rocky',
            'species' => $pet->species->value,
            'sex' => $pet->sex->value,
            'size' => $pet->size->value,
        ])
        ->call('save')
        ->assertNotified();

    expect($pet->refresh()->name)->toBe('Rocky');
});

test('admin can delete a pet', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $pet = Pet::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $this->tenant->id,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditPet::class, ['record' => $pet->id])
        ->callAction('delete')
        ->assertNotified();

    expect(Pet::find($pet->id))->toBeNull();
});

test('pet form requires field', function (string $field) {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    bootFilamentPanelAs($this->admin, $this->tenant);

    $validData = [
        'customer_id' => $customer->id,
        'name' => 'Buddy',
        'species' => 'dog',
        'sex' => 'm',
        'size' => 'medium',
    ];

    Livewire::test(CreatePet::class)
        ->fillForm(array_merge($validData, [$field => null]))
        ->call('create')
        ->assertHasFormErrors([$field]);
})->with(['name', 'species', 'size', 'customer_id']);
