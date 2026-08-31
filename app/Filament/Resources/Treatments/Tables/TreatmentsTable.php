<?php

declare(strict_types=1);

namespace App\Filament\Resources\Treatments\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                    ->searchable(['customers.first_name', 'customers.last_name']),

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
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
