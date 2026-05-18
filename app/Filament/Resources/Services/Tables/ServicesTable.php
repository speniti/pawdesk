<?php

declare(strict_types=1);

namespace App\Filament\Resources\Services\Tables;

use App\Enums\ServiceStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): ?string => $record->description)
                    ->wrap(),

                TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bath' => 'info',
                        'grooming' => 'primary',
                        'specialty' => 'danger',
                        'trimming' => 'warning',
                        'wellness' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label('Durata')
                    ->formatStateUsing(fn ($state): string => "{$state} min")
                    ->sortable(),

                TextColumn::make('base_price')
                    ->label('Prezzo base')
                    ->formatStateUsing(fn ($state): string => number_format($state / 100, 2, ',', '.').' €')
                    ->sortable(),

                TextColumn::make('coat')
                    ->label('Manto')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('category', 'asc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria'),

                SelectFilter::make('coat')
                    ->label('Manto'),

                SelectFilter::make('status')
                    ->label('Stato')
                    ->default(ServiceStatus::Active),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
