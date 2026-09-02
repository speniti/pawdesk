<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Tenant;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerCountWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();

        return [
            Stat::make('Clienti', $tenant->customers()->count())
                ->description('Clienti registrati')
                ->color('primary'),
        ];
    }
}
