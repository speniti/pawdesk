<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Widgets;

use App\Models\Customer;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class CustomerStats extends BaseWidget
{
    public ?Model $record = null;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var Customer $customer */
        $customer = $this->record;

        $totalSpendEur = $customer->treatments()->sum('final_price') / 100;

        return [
            Stat::make('Totale speso', number_format($totalSpendEur, 2, ',', '.').' €')
                ->description('Importo totale trattamenti')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success'),

            Stat::make('Appuntamenti', (string) $customer->appointments()->count())
                ->description('Totale appuntamenti')
                ->descriptionIcon(Heroicon::OutlinedCalendar)
                ->color('info'),

            Stat::make('Animali', (string) $customer->pets()->count())
                ->description('Pet registrati')
                ->descriptionIcon(Heroicon::OutlinedHeart)
                ->color('warning'),
        ];
    }
}
