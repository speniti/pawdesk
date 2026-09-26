<?php

declare(strict_types=1);

use App\Enums\Coat;
use App\Enums\Size;
use App\Models\Pet;
use App\Models\Service;
use App\Services\AppointmentPriceCalculator;

function petOfSizeAndCoat(Size $size, ?Coat $coat): Pet
{
    return new Pet([
        'size' => $size,
        'coat' => $coat?->value,
    ]);
}

test('resolvePrice returns variation price on exact size and coat match', function () {
    $service = new Service([
        'base_price' => 5000,
        'duration_minutes' => 60,
        'variations' => [
            ['size' => 'small', 'coat' => 'long', 'price' => 4000, 'duration_minutes' => 75],
            ['size' => 'small', 'coat' => 'short', 'price' => 3500, 'duration_minutes' => 60],
        ],
    ]);

    expect(AppointmentPriceCalculator::resolvePrice($service, petOfSizeAndCoat(Size::Small, Coat::Short)))->toBe(3500);
});

test('resolvePrice returns base price when coat does not match', function () {
    $service = new Service([
        'base_price' => 5000,
        'duration_minutes' => 60,
        'variations' => [
            ['size' => 'small', 'coat' => 'long', 'price' => 4000, 'duration_minutes' => 75],
        ],
    ]);

    expect(AppointmentPriceCalculator::resolvePrice($service, petOfSizeAndCoat(Size::Small, Coat::Short)))->toBe(5000);
});

test('resolvePrice returns base price when pet has no coat', function () {
    $service = new Service([
        'base_price' => 5000,
        'duration_minutes' => 60,
        'variations' => [
            ['size' => 'small', 'coat' => 'short', 'price' => 3500, 'duration_minutes' => 60],
        ],
    ]);

    expect(AppointmentPriceCalculator::resolvePrice($service, petOfSizeAndCoat(Size::Small, null)))->toBe(5000);
});

test('resolvePrice returns base price when pet is null', function () {
    $service = new Service([
        'base_price' => 5000,
        'duration_minutes' => 60,
        'variations' => [
            ['size' => 'small', 'coat' => 'short', 'price' => 3500, 'duration_minutes' => 60],
        ],
    ]);

    expect(AppointmentPriceCalculator::resolvePrice($service, null))->toBe(5000);
});

test('resolveDuration returns variation duration on exact match and base duration otherwise', function () {
    $service = new Service([
        'base_price' => 5000,
        'duration_minutes' => 60,
        'variations' => [
            ['size' => 'medium', 'coat' => 'curly', 'price' => 5500, 'duration_minutes' => 120],
        ],
    ]);

    expect(AppointmentPriceCalculator::resolveDuration($service, petOfSizeAndCoat(Size::Medium, Coat::Curly)))->toBe(120)
        ->and(AppointmentPriceCalculator::resolveDuration($service, petOfSizeAndCoat(Size::Medium, Coat::Long)))->toBe(60);
});

test('buildPivotData returns resolved price and duration per service', function () {
    $service1 = new Service([
        'base_price' => 3000,
        'duration_minutes' => 30,
        'variations' => [
            ['size' => 'large', 'coat' => 'double_coat', 'price' => 4000, 'duration_minutes' => 90],
        ],
    ]);
    $service1->id = 1;

    $service2 = new Service(['base_price' => 5000, 'duration_minutes' => 60, 'variations' => []]);
    $service2->id = 2;

    $pet = petOfSizeAndCoat(Size::Large, Coat::DoubleCoat);

    $result = AppointmentPriceCalculator::buildPivotData(collect([$service1, $service2]), $pet);

    expect($result)->toHaveCount(2)
        ->and($result[1])->toBe(['applied_price' => 4000, 'duration_minutes' => 90])
        ->and($result[2])->toBe(['applied_price' => 5000, 'duration_minutes' => 60]);
});

test('totalDuration sums resolved durations', function () {
    $service1 = new Service(['base_price' => 3000, 'duration_minutes' => 30, 'variations' => []]);
    $service2 = new Service([
        'base_price' => 5000,
        'duration_minutes' => 60,
        'variations' => [
            ['size' => 'small', 'coat' => 'smooth', 'price' => 3500, 'duration_minutes' => 45],
        ],
    ]);

    $pet = petOfSizeAndCoat(Size::Small, Coat::Smooth);

    expect(AppointmentPriceCalculator::totalDuration(collect([$service1, $service2]), $pet))->toBe(75);
});
