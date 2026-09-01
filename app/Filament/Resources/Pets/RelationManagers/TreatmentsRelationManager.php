<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets\RelationManagers;

use App\Filament\Resources\Treatments\TreatmentResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TreatmentsRelationManager extends RelationManager
{
    protected static ?string $pluralModelLabel = 'Trattamenti';

    protected static string $relationship = 'treatments';

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->columns([
                TextColumn::make('appointment.start_time')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

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
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('Visualizza')
                        ->icon(Heroicon::OutlinedEye)
                        ->url(fn ($record): string => TreatmentResource::getUrl('view', ['record' => $record])),
                    Action::make('edit')
                        ->label('Modifica')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->url(fn ($record): string => TreatmentResource::getUrl('edit', ['record' => $record])),
                ]),
            ]);
    }
}
