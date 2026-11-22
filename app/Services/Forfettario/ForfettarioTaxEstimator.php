<?php

declare(strict_types=1);

namespace App\Services\Forfettario;

use App\Enums\InpsMode;

class ForfettarioTaxEstimator
{
    public const int REVENUE_LIMIT_CENTS = 8_500_000;

    public function estimate(int $revenueCents, TaxConfiguration $config): TaxEstimate
    {
        $taxableIncomeCents = (int) round($revenueCents * $config->coefficientPercent / 100);
        $contributionsCents = $config->inpsMode === InpsMode::Percentage
            ? (int) round($taxableIncomeCents * ($config->inpsPercentage ?? 0.0) / 100)
            : ($config->inpsFixedAnnualCents ?? 0);
        $substituteTaxCents = (int) round(
            max(0, $taxableIncomeCents - $contributionsCents) * $config->substituteTaxRatePercent / 100
        );

        return new TaxEstimate(
            $revenueCents,
            $taxableIncomeCents,
            $contributionsCents,
            $substituteTaxCents,
            $contributionsCents + $substituteTaxCents,
        );
    }
}
