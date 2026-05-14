<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PetsRelationManager extends RelationManager
{
    protected static string $relationship = 'pets';

    public function table(Table $table): Table
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
                    ->searchable()
                    ->default('-'),

                TextColumn::make('size')
                    ->label('Taglia')
                    ->badge()
                    ->sortable(),

                TextColumn::make('sex')
                    ->label('Sesso')
                    ->badge()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    protected function headerActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
