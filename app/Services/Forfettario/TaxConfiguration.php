<?php

declare(strict_types=1);

namespace App\Services\Forfettario;

use App\Enums\InpsMode;

final readonly class TaxConfiguration
{
    /**
     * @param  float  $coefficientPercent  Coefficiente di redditività (es. 78)
     * @param  float  $substituteTaxRatePercent  Aliquota imposta sostitutiva (15 o 5)
     * @param  int|null  $inpsFixedAnnualCents  Contributi fissi annui in centesimi
     * @param  float|null  $inpsPercentage  Aliquota INPS sull'imponibile (es. 26.07)
     */
    public function __construct(
        public readonly float $coefficientPercent,
        public readonly float $substituteTaxRatePercent,
        public readonly InpsMode $inpsMode,
        public readonly ?int $inpsFixedAnnualCents,
        public readonly ?float $inpsPercentage,
    ) {}
}
