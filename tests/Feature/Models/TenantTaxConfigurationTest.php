<?php

declare(strict_types=1);

use App\Enums\InpsMode;
use App\Models\Tenant;
use App\Services\Forfettario\TaxConfiguration;

test('builds the tax configuration from tenant settings', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'tax_coefficient' => 78,
            'tax_rate' => 15,
            'inps_mode' => 'fixed',
            'inps_fixed_amount' => 4500,
        ],
    ]);

    $config = $tenant->forfettarioTaxConfiguration();

    expect($config)->toBeInstanceOf(TaxConfiguration::class)
        ->and($config->inpsMode)->toBe(InpsMode::Fixed)
        ->and($config->inpsFixedAnnualCents)->toBe(450_000)
        ->and($config->coefficientPercent)->toBe(78.0);
});

test('returns null when the fiscal settings are incomplete', function (array $settings) {
    $tenant = Tenant::factory()->create(['settings' => $settings]);

    expect($tenant->forfettarioTaxConfiguration())->toBeNull();
})->with([
    'empty settings' => [[]],
    'missing inps amount' => [['tax_coefficient' => 78, 'tax_rate' => 15, 'inps_mode' => 'fixed']],
    'missing inps percentage' => [['tax_coefficient' => 78, 'tax_rate' => 15, 'inps_mode' => 'percentage']],
    'missing mode' => [['tax_coefficient' => 78, 'tax_rate' => 15]],
]);
