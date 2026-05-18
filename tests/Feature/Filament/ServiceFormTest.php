<?php

declare(strict_types=1);

use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

test('validates duration_minutes must be greater than zero', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $service = Service::factory()->make(['duration_minutes' => 0]);

    Livewire::test(\App\Filament\Resources\Services\Pages\CreateService::class)
        ->fillForm([
            'name' => $service->name,
            'duration_minutes' => 0,
            'base_price' => $service->base_price / 100, // Convert cents to euros
            'category' => $service->category,
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasFormErrors(['duration_minutes' => 'min']);
});

test('validates base_price must be zero or positive', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $service = Service::factory()->make(['base_price' => -100]);

    Livewire::test(\App\Filament\Resources\Services\Pages\CreateService::class)
        ->fillForm([
            'name' => $service->name,
            'duration_minutes' => $service->duration_minutes,
            'base_price' => -100,
            'category' => $service->category,
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasFormErrors(['base_price' => 'min']);
});

test('validates duration_minutes cannot exceed 480 minutes', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $service = Service::factory()->make(['duration_minutes' => 481]);

    Livewire::test(\App\Filament\Resources\Services\Pages\CreateService::class)
        ->fillForm([
            'name' => $service->name,
            'duration_minutes' => 481,
            'base_price' => $service->base_price / 100,
            'category' => $service->category,
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasFormErrors(['duration_minutes' => 'max']);
});
