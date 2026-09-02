<?php

declare(strict_types=1);

use App\Filament\Widgets\CustomerCountWidget;
use App\Filament\Widgets\PetCountWidget;
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

function statValue(string $widgetClass): mixed
{
    $widget = Livewire::test($widgetClass)->instance();

    return invade($widget)->getStats()[0]->getValue();
}

test('counts tenant customers', function () {
    Customer::factory()->count(3)->for($this->tenant)->create();

    bootFilamentPanelAs($this->admin, $this->tenant);

    expect(statValue(CustomerCountWidget::class))->toBe(3);
});

test('counts tenant pets', function () {
    $customer = Customer::factory()->for($this->tenant)->create();
    Pet::factory()->count(2)->for($customer)->for($this->tenant)->create();

    bootFilamentPanelAs($this->admin, $this->tenant);

    expect(statValue(PetCountWidget::class))->toBe(2);
});

test('customer count excludes other tenants', function () {
    Customer::factory()->for(Tenant::factory()->create())->create();

    bootFilamentPanelAs($this->admin, $this->tenant);

    expect(statValue(CustomerCountWidget::class))->toBe(0);
});

test('pet count excludes other tenants', function () {
    $otherCustomer = Customer::factory()->for(Tenant::factory()->create())->create();
    Pet::factory()->for($otherCustomer)->for($otherCustomer->tenant)->create();

    bootFilamentPanelAs($this->admin, $this->tenant);

    expect(statValue(PetCountWidget::class))->toBe(0);
});
