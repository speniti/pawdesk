<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets\Schemas;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Pet;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Note comportamentali')
                            ->collapsed(false)
                            ->icon(Heroicon::OutlinedBookOpen)
                            ->schema([
                                TextEntry::make('behavioral_notes')
                                    ->hiddenLabel()
                                    ->placeholder('Nessuna nota')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Note sanitarie')
                            ->collapsed(false)
                            ->icon(Heroicon::OutlinedBookOpen)
                            ->schema([
                                TextEntry::make('health_notes')
                                    ->hiddenLabel()
                                    ->placeholder('Nessuna nota')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Grid::make(1)
                    ->schema([
                        Section::make('Dati anagrafici')
                            ->icon(Heroicon::OutlinedHeart)
                            ->inlineLabel()
                            ->schema([
                                TextEntry::make('date_of_birth')
                                    ->label('Data di nascita')
                                    ->alignEnd()
                                    ->date('d/m/Y')
                                    ->placeholder('Non specificata'),

                                TextEntry::make('customer.full_name')
                                    ->label('Proprietario')
                                    ->alignEnd()
                                    ->url(fn (Pet $record): string => CustomerResource::getUrl('view', ['record' => $record->customer_id])),
                            ]),

                        Section::make('Caratteristiche fisiche')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->inlineLabel()
                            ->schema([
                                TextEntry::make('species')
                                    ->label('Specie')
                                    ->alignEnd()
                                    ->badge(),

                                TextEntry::make('breed')
                                    ->label('Razza')
                                    ->alignEnd()
                                    ->placeholder('Non specificata'),

                                TextEntry::make('sex')
                                    ->label('Sesso')
                                    ->alignEnd()
                                    ->badge(),

                                TextEntry::make('size')
                                    ->label('Taglia')
                                    ->alignEnd()
                                    ->badge(),

                                TextEntry::make('coat')
                                    ->label('Manto')
                                    ->alignEnd()
                                    ->badge(),
                            ]),
                    ]),
            ]);
    }
}
