<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets\Tables;

use App\Filament\Resources\Pets\PetResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
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
                    ->searchable(['first_name', 'last_name']),

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
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ])
                    ->icon(Heroicon::OutlinedEllipsisVertical),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->emptyStateHeading('Nessun animale trovato')
            ->emptyStateDescription('Prova a rimuovere i filtri oppure creane uno nuovo.')
            ->emptyStateIcon(PetResource::getNavigationIcon());
    }
}
