<?php

declare(strict_types=1);

namespace App\Filament\Resources\Services\Tables;

use App\Enums\Coat;
use App\Enums\ServiceCategory;
use App\Enums\ServiceStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

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
                    ->color(fn (ServiceCategory $state): string => match ($state) {
                        ServiceCategory::Grooming => 'primary',
                        ServiceCategory::Bath => 'info',
                        ServiceCategory::Trimming => 'warning',
                        ServiceCategory::Wellness => 'success',
                        ServiceCategory::Specialty => 'danger',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label('Durata')
                    ->formatStateUsing(fn ($state): string => "{$state} min")
                    ->sortable(),

                TextColumn::make('base_price')
                    ->label('Prezzo base')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => Number::currency($state / 100, in: 'EUR', locale: 'it'))
                    ->alignment('right'),

                TextColumn::make('coat')
                    ->label('Manto')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?Coat $state): ?string => $state?->getLabel())
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->color(fn (ServiceStatus $state): string => $state->getColor()),

                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('category', 'asc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(ServiceCategory::class),

                SelectFilter::make('coat')
                    ->label('Manto')
                    ->options(Coat::class)
                    ->placeholder('Tutti'),

                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(ServiceStatus::class)
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
