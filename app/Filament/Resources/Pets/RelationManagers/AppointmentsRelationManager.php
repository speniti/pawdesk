<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->columns([
                TextColumn::make('start_time')
                    ->label('Inizio')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge(),

                TextColumn::make('end_time')
                    ->label('Fine')
                    ->dateTime(),

                TextColumn::make('internal_notes')
                    ->label('Note interne')
                    ->limit(50)
                    ->default('-'),
            ])
            ->defaultSort('start_time', 'desc');
    }
}
