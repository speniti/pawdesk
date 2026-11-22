<?php

declare(strict_types=1);

namespace App\Filament\Pages\RevenueDashboard\Widgets;

use App\Filament\Pages\RevenueDashboard;
use App\Models\Tenant;
use App\Services\Forfettario\ForfettarioTaxEstimator;
use App\Services\TenantRevenue;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class RevenueStats extends BaseWidget
{
    protected ?string $pollingInterval = null;

    /**
     * Defense in depth: the widget renders only inside the admin-gated page,
     * but keep the page's role gate here so it can never be exposed by
     * registering it elsewhere (e.g. on a staff-visible page).
     */
    public static function canView(): bool
    {
        return RevenueDashboard::canAccess();
    }

    /** Threshold usage color: danger once 80% of the forfettario limit is reached. */
    public static function thresholdColor(int $ytdCents): string
    {
        return $ytdCents / ForfettarioTaxEstimator::REVENUE_LIMIT_CENTS >= 0.8 ? 'danger' : 'gray';
    }

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();
        $revenue = app(TenantRevenue::class);
        $year = now()->year;

        $ytd = $revenue->totalForYear($tenant, $year);
        $samePeriodLastYear = $revenue->totalBetween(
            $tenant,
            now()->subYear()->startOfYear(),
            now()->subYear()->endOfDay(),
        );

        $config = $tenant->forfettarioTaxConfiguration();
        $estimate = $config !== null
            ? app(ForfettarioTaxEstimator::class)->estimate($ytd, $config)
            : null;

        return [
            Stat::make("Fatturato {$year}", RevenueDashboard::money($ytd))
                ->description($this->yoyDescription($ytd, $samePeriodLastYear))
                ->descriptionIcon($this->yoyIcon($ytd, $samePeriodLastYear))
                ->descriptionColor($this->yoyColor($ytd, $samePeriodLastYear)),

            $estimate !== null
                ? Stat::make('Stima tasse anno', RevenueDashboard::money($estimate->totalTaxCents))
                    ->description('INPS '.RevenueDashboard::money($estimate->inpsContributionsCents)
                        .' + imposta '.RevenueDashboard::money($estimate->substituteTaxCents))
                    ->descriptionColor('warning')
                : Stat::make('Stima tasse anno', '—')
                    ->description('Configura la sezione Fiscalità nelle impostazioni')
                    ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                    ->descriptionColor('warning'),

            $estimate !== null
                ? Stat::make('Da accantonare', Number::percentage($estimate->setAsideRate() * 100, maxPrecision: 1, locale: 'it'))
                    ->description('≈ '.RevenueDashboard::money((int) round($estimate->setAsideRate() * 10_000)).' ogni 100 € incassati')
                    ->descriptionColor('success')
                : Stat::make('Da accantonare', '—')
                    ->description('Richiede la configurazione fiscale')
                    ->descriptionColor('gray'),

            Stat::make('Soglia forfettario', Number::percentage(
                $ytd / ForfettarioTaxEstimator::REVENUE_LIMIT_CENTS * 100,
                maxPrecision: 1,
                locale: 'it',
            ))
                ->description(RevenueDashboard::money($ytd).' su '.RevenueDashboard::money(ForfettarioTaxEstimator::REVENUE_LIMIT_CENTS))
                ->descriptionIcon(Heroicon::OutlinedFlag)
                ->descriptionColor(self::thresholdColor($ytd)),
        ];
    }

    /** @return 'success' | 'danger' | 'gray' */
    private function yoyColor(int $ytd, int $samePeriodLastYear): string
    {
        if ($samePeriodLastYear === 0) {
            return 'gray';
        }

        return $ytd >= $samePeriodLastYear ? 'success' : 'danger';
    }

    private function yoyDescription(int $ytd, int $samePeriodLastYear): string
    {
        if ($samePeriodLastYear === 0) {
            return 'Nessun dato nello stesso periodo dell\'anno scorso';
        }

        $delta = ($ytd - $samePeriodLastYear) / $samePeriodLastYear * 100;

        return ($delta >= 0 ? '+' : '').Number::percentage($delta, maxPrecision: 1, locale: 'it')
            .' vs stesso periodo '.(now()->year - 1);
    }

    private function yoyIcon(int $ytd, int $samePeriodLastYear): Heroicon
    {
        if ($samePeriodLastYear === 0) {
            return Heroicon::OutlinedMinus;
        }

        return $ytd >= $samePeriodLastYear ? Heroicon::OutlinedArrowTrendingUp : Heroicon::OutlinedArrowTrendingDown;
    }
}
