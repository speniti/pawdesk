<?php

declare(strict_types=1);

use App\Enums\InpsMode;
use App\Services\Forfettario\ForfettarioTaxEstimator;
use App\Services\Forfettario\TaxConfiguration;

// Revenue €100.000, coefficient 78%, gestione separata 26%, imposta 15%:
// imponibile 78.000; INPS 20.280; imposta 15% x 57.720 = 8.658; totale 28.938.
test('estimates taxes with percentage inps mode', function () {
    $estimate = app(ForfettarioTaxEstimator::class)->estimate(
        10_000_000,
        new TaxConfiguration(78.0, 15.0, InpsMode::Percentage, null, 26.0),
    );

    expect($estimate->taxableIncomeCents)->toBe(7_800_000)
        ->and($estimate->inpsContributionsCents)->toBe(2_028_000)
        ->and($estimate->substituteTaxCents)->toBe(865_800)
        ->and($estimate->totalTaxCents)->toBe(2_893_800)
        ->and($estimate->setAsideRate())->toBe(0.2894);
});

// Revenue €50.000, coefficient 78%, contributi fissi €4.500, imposta 15%:
// imponibile 39.000; imposta 15% x 34.500 = 5.175; totale 9.675.
test('estimates taxes with fixed inps contributions', function () {
    $estimate = app(ForfettarioTaxEstimator::class)->estimate(
        5_000_000,
        new TaxConfiguration(78.0, 15.0, InpsMode::Fixed, 450_000, null),
    );

    expect($estimate->inpsContributionsCents)->toBe(450_000)
        ->and($estimate->substituteTaxCents)->toBe(517_500)
        ->and($estimate->totalTaxCents)->toBe(967_500);
});

// Con contributi fissi superiori all'imponibile l'imposta è 0 (non negativa),
// ma i contributi restano dovuti: il totale può superare il fatturato.
test('clamps substitute tax to zero when contributions exceed taxable income', function () {
    $estimate = app(ForfettarioTaxEstimator::class)->estimate(
        100_000, // €1.000
        new TaxConfiguration(78.0, 15.0, InpsMode::Fixed, 450_000, null),
    );

    expect($estimate->taxableIncomeCents)->toBe(78_000)
        ->and($estimate->substituteTaxCents)->toBe(0)
        ->and($estimate->totalTaxCents)->toBe(450_000)
        ->and($estimate->setAsideRate())->toBe(4.5);
});

test('handles zero revenue', function () {
    $estimate = app(ForfettarioTaxEstimator::class)->estimate(
        0,
        new TaxConfiguration(78.0, 15.0, InpsMode::Percentage, null, 26.0),
    );

    expect($estimate->taxableIncomeCents)->toBe(0)
        ->and($estimate->totalTaxCents)->toBe(0)
        ->and($estimate->setAsideRate())->toBe(0.0);
});

test('exposes the forfettario revenue limit', function () {
    expect(ForfettarioTaxEstimator::REVENUE_LIMIT_CENTS)->toBe(8_500_000);
});
