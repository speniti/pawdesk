<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingAppointmentsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Appointment::query()
                ->where('tenant_id', Filament::getTenant()->id)
                ->where('status', AppointmentStatus::Confirmed)
                ->where('end_time', '>=', now())
                ->with(['customer', 'pet', 'services'])
                ->limit(5))
            ->heading('Prossimi appuntamenti')
            ->defaultSort('start_time')
            ->paginated(false)
            ->columns([
                TextColumn::make('start_time')
                    ->label('Data e ora')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('pet.name')
                    ->label('Animale'),
                TextColumn::make('customer.fullName')
                    ->label('Cliente'),
                TextColumn::make('services.name')
                    ->label('Servizi')
                    ->badge(),
            ]);
    }
}
