<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets\Widgets;

use App\Models\Pet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class PetStats extends BaseWidget
{
    public ?Model $record = null;

    /** @var int | array<string, ?int> | null */
    protected int|array|null $columns = 2;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var Pet $pet */
        $pet = $this->record->loadCount('appointments')->loadSum('treatments', 'final_price');

        $totalSpendEur = ($pet->treatments_sum_final_price ?? 0) / 100;

        return [
            Stat::make('Spesa totale', Number::currency($totalSpendEur, in: 'EUR', locale: 'it')),
            Stat::make('Appuntamenti', (string) $pet->appointments_count),
        ];
    }
}
