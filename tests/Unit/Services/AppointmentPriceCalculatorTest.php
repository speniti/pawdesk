<?php

declare(strict_types=1);

use App\Enums\Size;
use App\Models\Service;
use App\Services\AppointmentPriceCalculator;

test('resolvePrice returns size price when matching size exists', function () {
    $service = new Service([
        'base_price' => 5000,
        'size_prices' => [
            ['size' => 'small', 'price' => 3500],
            ['size' => 'large', 'price' => 6000],
        ],
    ]);

    expect(AppointmentPriceCalculator::resolvePrice($service, Size::Small))->toBe(3500);
});

test('resolvePrice returns base price when no matching size', function () {
    $service = new Service([
        'base_price' => 5000,
        'size_prices' => [
            ['size' => 'small', 'price' => 3500],
        ],
    ]);

    expect(AppointmentPriceCalculator::resolvePrice($service, Size::Large))->toBe(5000);
});

test('resolvePrice returns base price when pet size is null', function () {
    $service = new Service([
        'base_price' => 5000,
        'size_prices' => [
            ['size' => 'small', 'price' => 3500],
        ],
    ]);

    expect(AppointmentPriceCalculator::resolvePrice($service, null))->toBe(5000);
});

test('buildPivotData returns correct per-service map', function () {
    $service1 = new Service(['base_price' => 3000, 'duration_minutes' => 30, 'size_prices' => []]);
    $service1->id = 1;

    $service2 = new Service(['base_price' => 5000, 'duration_minutes' => 60, 'size_prices' => []]);
    $service2->id = 2;

    $services = collect([$service1, $service2]);

    $result = AppointmentPriceCalculator::buildPivotData($services, null);

    expect($result)->toHaveCount(2)
        ->and($result[1])->toBe(['applied_price' => 3000, 'duration_minutes' => 30])
        ->and($result[2])->toBe(['applied_price' => 5000, 'duration_minutes' => 60]);
});

test('buildPivotData handles mixed size overrides', function () {
    $service1 = new Service([
        'base_price' => 5000,
        'duration_minutes' => 45,
        'size_prices' => [['size' => 'small', 'price' => 3500]],
    ]);
    $service1->id = 1;

    $service2 = new Service([
        'base_price' => 4000,
        'duration_minutes' => 30,
        'size_prices' => [],
    ]);
    $service2->id = 2;

    $services = collect([$service1, $service2]);

    $result = AppointmentPriceCalculator::buildPivotData($services, Size::Small);

    expect($result)->toHaveCount(2)
        ->and($result[1])->toBe(['applied_price' => 3500, 'duration_minutes' => 45])
        ->and($result[2])->toBe(['applied_price' => 4000, 'duration_minutes' => 30]);
});
