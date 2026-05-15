<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets\Widgets;

use App\Models\Pet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class PetStats extends BaseWidget
{
    public ?Model $record = null;

    /** @var int | array<string, ?int> | null */
    protected int|array|null $columns = 2;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var Pet $pet */
        $pet = $this->record;

        $totalSpendEur = $pet->treatments()->sum('final_price') / 100;

        return [
            Stat::make('Spesa totale', number_format($totalSpendEur, 2, ',', '.').' €'),
            Stat::make('Appuntamenti', (string) $pet->appointments()->count()),
        ];
    }
}
