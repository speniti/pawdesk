<?php

declare(strict_types=1);

namespace App\Filament\Resources\Services\Schemas;

use App\Enums\Coat;
use App\Enums\ServiceCategory;
use App\Enums\ServiceStatus;
use App\Enums\Size;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Number;

class ServiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Informazioni generali')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nome')
                            ->columnSpanFull(),

                        TextEntry::make('description')
                            ->label('Descrizione')
                            ->placeholder('Nessuna descrizione')
                            ->columnSpanFull(),

                        TextEntry::make('category')
                            ->label('Categoria')
                            ->badge()
                            ->color(fn (ServiceCategory $state): string => $state->getColor()),

                        TextEntry::make('coat')
                            ->label('Tipo di Manto')
                            ->formatStateUsing(fn (?Coat $state): ?string => $state?->getLabel())
                            ->placeholder('Non specificato'),

                        TextEntry::make('duration_minutes')
                            ->label('Durata')
                            ->formatStateUsing(fn ($state): string => "{$state} min"),

                        TextEntry::make('base_price')
                            ->label('Prezzo base')
                            ->formatStateUsing(fn ($state): string => Number::format($state / 100, precision: 2, locale: 'it').' €'),

                        TextEntry::make('status')
                            ->label('Stato')
                            ->badge()
                            ->color(fn (ServiceStatus $state): string => $state->getColor()),

                        TextEntry::make('combinable')
                            ->label('Combinabile')
                            ->formatStateUsing(fn ($state): string => $state ? 'Sì' : 'No'),
                    ])
                    ->columns(3),

                Section::make('Prezzi per taglia')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->schema([
                        RepeatableEntry::make('size_prices')
                            ->hiddenLabel()
                            ->hidden(fn ($record): bool => empty($record->size_prices))
                            ->schema([
                                TextEntry::make('size')
                                    ->label('Taglia')
                                    ->formatStateUsing(fn ($state): string => Size::from($state)->getLabel()),

                                TextEntry::make('price')
                                    ->label('Prezzo')
                                    ->formatStateUsing(fn ($state): string => Number::format($state / 100, precision: 2, locale: 'it').' €'),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }
}
