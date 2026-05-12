<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('species')
                    ->label('Specie')
                    ->badge()
                    ->sortable(),

                TextColumn::make('breed')
                    ->label('Razza')
                    ->searchable(),

                TextColumn::make('size')
                    ->label('Taglia')
                    ->badge()
                    ->sortable(),

                TextColumn::make('customer.full_name')
                    ->label('Proprietario')
                    ->searchable(['customers.first_name', 'customers.last_name']),

                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('species')
                    ->label('Specie'),

                SelectFilter::make('size')
                    ->label('Taglia'),

                SelectFilter::make('coat')
                    ->label('Manto'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
