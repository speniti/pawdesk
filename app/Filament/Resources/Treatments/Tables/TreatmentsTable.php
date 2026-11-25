<?php

declare(strict_types=1);

namespace App\Filament\Resources\Treatments\Tables;

use App\Filament\Resources\Treatments\TreatmentResource;
use App\Models\Customer;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TreatmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('appointment.start_time')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('pet.name')
                    ->label('Animale')
                    ->searchable(),

                TextColumn::make('customer.full_name')
                    ->label('Cliente')
                    ->searchable(['first_name', 'last_name']),

                TextColumn::make('actual_duration_minutes')
                    ->label('Durata effettiva')
                    ->suffix(' min'),

                TextColumn::make('final_price')
                    ->label('Prezzo finale')
                    ->formatStateUsing(fn (int $state): string => '€'.number_format($state / 100, 2))
                    ->sortable(),

                IconColumn::make('visible_to_customer')
                    ->label('Visibile al cliente')
                    ->boolean(),
            ])
            ->defaultSort('appointment.start_time', 'desc')
            ->filters([
                // The appointment date is the operational timeline a groomer
                // browses: range filter working with either bound alone.
                Filter::make('periodo')
                    ->label('Periodo')
                    ->form([
                        DatePicker::make('dal')
                            ->label('Dal'),
                        DatePicker::make('al')
                            ->label('Al'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['dal'] ?? null) && blank($data['al'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('appointment', function (Builder $appointment) use ($data): void {
                            if ($data['dal'] ?? null) {
                                $appointment->where('start_time', '>=', Carbon::parse($data['dal'])->startOfDay());
                            }

                            if ($data['al'] ?? null) {
                                $appointment->where('start_time', '<=', Carbon::parse($data['al'])->endOfDay());
                            }
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['dal'] ?? null) {
                            $indicators[] = 'Dal '.Carbon::parse($data['dal'])->format('d/m/Y');
                        }

                        if ($data['al'] ?? null) {
                            $indicators[] = 'Al '.Carbon::parse($data['al'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),

                // Relationship options are pinned to the current tenant
                // explicitly: the filter query bypasses the resource-level
                // scoping, and the tenant posture must not depend on Filament
                // defaults (see #93).
                SelectFilter::make('cliente')
                    ->label('Cliente')
                    ->relationship(
                        'customer',
                        'last_name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                    )
                    ->getOptionLabelFromRecordUsing(
                        // Ordered and searched by last_name (indexed column),
                        // but labelled like every other surface shows it.
                        fn (Customer $record): string => "{$record->last_name} {$record->first_name}",
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('animale')
                    ->label('Animale')
                    ->relationship(
                        'pet',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()),
                    )
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('visible_to_customer')
                    ->label('Visibile al cliente'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ])
                    ->icon(Heroicon::OutlinedEllipsisVertical),
            ])
            ->emptyStateHeading('Nessun trattamento trovato')
            ->emptyStateDescription('I trattamenti vengono generati automaticamente al completamento degli appuntamenti.')
            ->emptyStateIcon(TreatmentResource::getNavigationIcon());
    }
}
