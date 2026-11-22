<?php

declare(strict_types=1);

namespace App\Services\Forfettario;

final readonly class TaxEstimate
{
    public function __construct(
        public readonly int $revenueCents,
        public readonly int $taxableIncomeCents,
        public readonly int $inpsContributionsCents,
        public readonly int $substituteTaxCents,
        public readonly int $totalTaxCents,
    ) {}

    /**
     * Share of the revenue to set aside for taxes. May exceed 1.0 early in the
     * year, when fixed INPS contributions outweigh the revenue accrued so far.
     */
    public function setAsideRate(): float
    {
        if ($this->revenueCents === 0) {
            return 0.0;
        }

        return round($this->totalTaxCents / $this->revenueCents, 4);
    }
}
