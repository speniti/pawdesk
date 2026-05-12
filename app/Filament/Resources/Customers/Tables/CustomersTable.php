<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nome completo')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('first_name', $direction)),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Telefono')
                    ->searchable(),

                TextColumn::make('preferred_channel')
                    ->label('Canale preferito')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('preferred_channel')
                    ->label('Canale preferito'),

                TernaryFilter::make('marketing_consent_at')
                    ->label('Consenso marketing')
                    ->nullable()
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('marketing_consent_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('marketing_consent_at'),
                    ),

                Filter::make('without_appointments')
                    ->label('Senza appuntamenti')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('appointments')),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
