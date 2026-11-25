<?php

declare(strict_types=1);

namespace App\Filament\Pages\RevenueDashboard\Widgets;

use App\Filament\Pages\RevenueDashboard;
use App\Models\Tenant;
use App\Services\TenantRevenue;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    /**
     * Header widgets live on a 2-column grid; the base Widget span of 1
     * would half the chart's width (#98). Full span stacks it under the
     * full-width stats overview.
     */
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Fatturato mensile';

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

    /**
     * @return array{datasets: array<int, array{label: string, data: array<int, float>}>, labels: array<int, string>}
     */
    protected function getData(): array
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();
        $revenue = app(TenantRevenue::class);
        $year = now()->year;

        return [
            'datasets' => [
                [
                    'label' => (string) $year,
                    'data' => $this->inEuros($revenue->monthlyForYear($tenant, $year)),
                ],
                [
                    'label' => (string) ($year - 1),
                    'data' => $this->inEuros($revenue->monthlyForYear($tenant, $year - 1)),
                ],
            ],
            'labels' => ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @param  array<int, int>  $monthlyCents
     * @return array<int, float>
     */
    private function inEuros(array $monthlyCents): array
    {
        return array_map(static fn (int $cents): float => round($cents / 100, 2), array_values($monthlyCents));
    }
}
