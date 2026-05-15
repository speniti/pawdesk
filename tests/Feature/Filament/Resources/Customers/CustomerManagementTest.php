<?php

declare(strict_types=1);

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

test('admin can create a customer', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'mario.rossi@example.com',
            'phone' => '+39 02 1234567',
            'preferred_channel' => 'email',
        ])
        ->call('create')
        ->assertNotified();

    $customer = Customer::where('email', 'mario.rossi@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->first_name)->toBe('Mario')
        ->and($customer->last_name)->toBe('Rossi')
        ->and($customer->gdpr_policy_sent_at)->not->toBeNull();
});

test('admin can update a customer', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditCustomer::class, ['record' => $customer->id])
        ->fillForm([
            'first_name' => 'Luigi',
            'last_name' => 'Bianchi',
            'email' => $customer->email,
            'phone' => $customer->phone,
            'preferred_channel' => $customer->preferred_channel->value,
        ])
        ->call('save')
        ->assertNotified();

    expect($customer->refresh()->first_name)->toBe('Luigi');
});

test('admin can delete a customer', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditCustomer::class, ['record' => $customer->id])
        ->callAction('delete')
        ->assertNotified();

    expect(Customer::find($customer->id))->toBeNull();
});

test('duplicate email for same tenant fails validation', function () {
    Customer::factory()->create([
        'tenant_id' => $this->tenant->id,
        'email' => 'shared@example.com',
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'shared@example.com',
            'phone' => '+39 02 1234567',
            'preferred_channel' => 'email',
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);
});

test('duplicate email for different tenant is allowed', function () {
    $otherTenant = Tenant::factory()->create();
    Customer::factory()->create([
        'tenant_id' => $otherTenant->id,
        'email' => 'shared@example.com',
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'shared@example.com',
            'phone' => '+39 02 1234567',
            'preferred_channel' => 'email',
        ])
        ->call('create')
        ->assertNotified();

    expect(Customer::withoutGlobalScopes()->where('email', 'shared@example.com')->count())->toBe(2);
});
