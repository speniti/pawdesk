<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Pets\PetResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PetsRelationManager extends RelationManager
{
    protected static string $relationship = 'pets';

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
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
                ActionGroup::make([
                    Action::make('view')
                        ->label('Visualizza')
                        ->icon(Heroicon::OutlinedEye)
                        ->url(fn ($record): string => PetResource::getUrl('view', ['record' => $record])),
                    Action::make('edit')
                        ->label('Modifica')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->url(fn ($record): string => PetResource::getUrl('edit', ['record' => $record])),
                ]),
            ]);
    }

    protected function headerActions(): array
    {
        return [
            Action::make('create')
                ->label('Nuovo pet')
                ->icon(Heroicon::OutlinedPlus)
                ->url(fn (): string => PetResource::getUrl('create')),
        ];
    }
}
