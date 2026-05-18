<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class CustomerStats extends BaseWidget
{
    public ?Model $record = null;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var Customer $customer */
        $customer = $this->record->loadCount(['appointments', 'pets'])->loadSum('treatments', 'final_price');

        $totalSpendEur = ($customer->treatments_sum_final_price ?? 0) / 100;

        return [
            Stat::make('Spesa totale', Number::currency($totalSpendEur, in: 'EUR', locale: 'it')),
            Stat::make('Appuntamenti', (string) $customer->appointments_count),
            Stat::make('Pets', (string) $customer->pets_count),
        ];
    }
}
