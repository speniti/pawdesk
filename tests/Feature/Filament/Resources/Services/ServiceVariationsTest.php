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

test('can save variations matrix', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(App\Filament\Resources\Services\Pages\CreateService::class)
        ->fillForm([
            'name' => 'Full Grooming Package',
            'duration_minutes' => 60,
            'base_price' => 50.00, // €50.00 in euros for form input
            'category' => 'grooming',
            'status' => 'active',
            'combinable' => true,
            'variations' => [
                ['size' => 'toy', 'coat' => 'short', 'price' => 25.00, 'duration_minutes' => 45],
                ['size' => 'small', 'coat' => 'long', 'price' => 35.00, 'duration_minutes' => 60],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $service = Service::where('name', 'Full Grooming Package')->first();

    expect($service)->not->toBeNull()
        ->and($service->variations)->toBeArray()
        ->and($service->variations)->toHaveCount(2)
        ->and($service->variations[0]['size'])->toBe('toy')
        ->and($service->variations[0]['coat'])->toBe('short')
        ->and($service->variations[0]['price'])->toBe(2500)    // Stored in cents
        ->and($service->variations[0]['duration_minutes'])->toBe(45)
        ->and($service->variations[1]['size'])->toBe('small')
        ->and($service->variations[1]['coat'])->toBe('long')
        ->and($service->variations[1]['price'])->toBe(3500)    // Stored in cents
        ->and($service->variations[1]['duration_minutes'])->toBe(60);
});

test('rejects duplicate size and coat combinations', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(App\Filament\Resources\Services\Pages\CreateService::class)
        ->fillForm([
            'name' => 'Duplicate Combinations',
            'duration_minutes' => 30,
            'base_price' => 20.00, // €20.00 in euros for form input
            'category' => 'bath',
            'status' => 'active',
            'variations' => [
                ['size' => 'toy', 'coat' => 'short', 'price' => 25.00, 'duration_minutes' => 45],
                ['size' => 'toy', 'coat' => 'short', 'price' => 30.00, 'duration_minutes' => 50],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['variations']);

    expect(Service::where('name', 'Duplicate Combinations')->exists())->toBeFalse();
});

test('can save a service without variations', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(App\Filament\Resources\Services\Pages\CreateService::class)
        ->fillForm([
            'name' => 'Nail Trim',
            'duration_minutes' => 15,
            'base_price' => 10.00, // €10.00 in euros for form input
            'category' => 'trimming',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $service = Service::where('name', 'Nail Trim')->first();

    expect($service)->not->toBeNull()
        ->and($service->variations)->toBeArray()
        ->and($service->variations)->toBeEmpty();
});
