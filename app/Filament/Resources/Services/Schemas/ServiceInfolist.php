<?php

declare(strict_types=1);

namespace App\Filament\Resources\Services\Schemas;

use App\Enums\Coat;
use App\Enums\ServiceCategory;
use App\Enums\ServiceStatus;
use App\Enums\Size;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Number;

class ServiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(2)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Informazioni generali')
                            ->columns(2)
                            ->icon(Heroicon::OutlinedInformationCircle)
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
                                    ->color(fn (ServiceCategory $state): string => match ($state) {
                                        ServiceCategory::Grooming => 'primary',
                                        ServiceCategory::Bath => 'info',
                                        ServiceCategory::Trimming => 'warning',
                                        ServiceCategory::Wellness => 'success',
                                        ServiceCategory::Specialty => 'danger',
                                    }),

                                TextEntry::make('coat')
                                    ->label('Manto')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(fn (?Coat $state): ?string => $state?->getLabel())
                                    ->placeholder('-'),

                                TextEntry::make('duration_minutes')
                                    ->label('Durata')
                                    ->formatStateUsing(fn ($state): string => "{$state} min"),

                                TextEntry::make('combinable')
                                    ->label('Combinabile')
                                    ->boolean(),
                            ]),

                        Section::make('Prezzi per taglia')
                            ->collapsed(false)
                            ->icon(Heroicon::OutlinedCurrencyEuro)
                            ->hidden(fn ($record): bool => empty($record->size_prices))
                            ->schema([
                                RepeatableEntry::make('size_prices')
                                    ->hiddenLabel()
                                    ->contained(false)
                                    ->schema([
                                        TextEntry::make('size')
                                            ->label('Taglia')
                                            ->badge()
                                            ->formatStateUsing(fn ($state): string => Size::from($state)->getLabel()),

                                        TextEntry::make('price')
                                            ->label('Prezzo')
                                            ->formatStateUsing(fn ($state): string => Number::format($state / 100, 2, ',', '.').' €')
                                            ->alignEnd(),
                                    ]),
                            ]),
                    ]),

                Grid::make(1)
                    ->schema([
                        Section::make('Prezzo e stato')
                            ->icon(Heroicon::OutlineTag)
                            ->schema([
                                TextEntry::make('base_price')
                                    ->label('Prezzo base')
                                    ->inlineLabel()
                                    ->alignEnd()
                                    ->formatStateUsing(fn ($state): string => Number::format($state / 100, 2, ',', '.').' €'),

                                TextEntry::make('status')
                                    ->label('Stato')
                                    ->inlineLabel()
                                    ->alignEnd()
                                    ->badge()
                                    ->color(fn (ServiceStatus $state): string => $state->getColor()),
                            ]),
                    ]),
            ]);
    }
}
