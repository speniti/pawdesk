<?php

declare(strict_types=1);

use App\Enums\Size;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

test('can save size prices matrix', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(App\Filament\Resources\Services\Pages\CreateService::class)
        ->fillForm([
            'name' => 'Full Grooming Package',
            'duration_minutes' => 60,
            'base_price' => 50.00, // €50.00 in euros for form input
            'category' => 'grooming',
            'status' => 'active',
            'combinable' => true,
            'size_prices' => [
                ['size' => 'toy', 'price' => 25.00],    // €25.00 in euros for form input
                ['size' => 'small', 'price' => 35.00],  // €35.00 in euros for form input
                ['size' => 'medium', 'price' => 45.00], // €45.00 in euros for form input
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $service = Service::where('name', 'Full Grooming Package')->first();

    expect($service)->not->toBeNull()
        ->and($service->size_prices)->toBeArray()
        ->and($service->size_prices)->toHaveCount(3)
        ->and($service->size_prices[0]['size'])->toBe('toy')
        ->and($service->size_prices[0]['price'])->toBe(2500)    // Stored in cents
        ->and($service->size_prices[1]['size'])->toBe('small')
        ->and($service->size_prices[1]['price'])->toBe(3500)  // Stored in cents
        ->and($service->size_prices[2]['size'])->toBe('medium')
        ->and($service->size_prices[2]['price'])->toBe(4500); // Stored in cents
});

test('can read size prices from service', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $sizePrices = [
        ['size' => 'toy', 'price' => 2000],    // €20.00 in cents
        ['size' => 'small', 'price' => 3000],  // €30.00 in cents
        ['size' => 'medium', 'price' => 4000], // €40.00 in cents
        ['size' => 'large', 'price' => 5500],  // €55.00 in cents
        ['size' => 'giant', 'price' => 7000],  // €70.00 in cents
    ];

    $service = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Premium Grooming',
        'base_price' => 5000, // €50.00 in cents
        'size_prices' => $sizePrices,
    ]);

    expect($service->size_prices)->toBeArray()
        ->and($service->size_prices)->toHaveCount(5)
        ->and($service->size_prices[0]['size'])->toBe('toy')
        ->and($service->size_prices[0]['price'])->toBe(2000)    // €20.00
        ->and($service->size_prices[1]['size'])->toBe('small')
        ->and($service->size_prices[1]['price'])->toBe(3000)  // €30.00
        ->and($service->size_prices[2]['size'])->toBe('medium')
        ->and($service->size_prices[2]['price'])->toBe(4000) // €40.00
        ->and($service->size_prices[3]['size'])->toBe('large')
        ->and($service->size_prices[3]['price'])->toBe(5500)  // €55.00
        ->and($service->size_prices[4]['size'])->toBe('giant')
        ->and($service->size_prices[4]['price'])->toBe(7000); // €70.00
});

test('uses base price when size price is not set', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $basePriceInCents = 5000; // €50.00

    // Service with only some size prices set
    $service = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Partial Size Pricing',
        'base_price' => $basePriceInCents,
        'size_prices' => [
            ['size' => 'toy', 'price' => 2500],    // €25.00 - custom price
            ['size' => 'small', 'price' => 3500],  // €35.00 - custom price
            // medium, large, giant not set - should use base price
        ],
    ]);

    expect($service->size_prices)->toBeArray()
        ->and($service->size_prices)->toHaveCount(2)
        ->and($service->size_prices[0]['size'])->toBe('toy')
        ->and($service->size_prices[0]['price'])->toBe(2500)    // €25.00 - custom price
        ->and($service->size_prices[1]['size'])->toBe('small')
        ->and($service->size_prices[1]['price'])->toBe(3500)  // €35.00 - custom price
        ->and($service->base_price)->toBe($basePriceInCents); // €50.00

    // Verify that unset sizes are not in the array (would use base price in business logic)
    expect($service->size_prices)->not->toHaveKey('2')
        ->and($service->size_prices)->not->toHaveKey('3')
        ->and($service->size_prices)->not->toHaveKey('4');
});
