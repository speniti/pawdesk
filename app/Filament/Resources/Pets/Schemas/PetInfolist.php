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
                        Section::make('Dati anagrafici')
                            ->columns(2)
                            ->icon(Heroicon::OutlinedHeart)
                            ->schema([
                                TextEntry::make('breed')
                                    ->label('Razza')
                                    ->placeholder('Non specificata'),

                                TextEntry::make('date_of_birth')
                                    ->label('Data di nascita')
                                    ->date('d/m/Y')
                                    ->placeholder('Non specificata'),
                            ]),

                        Section::make('Note comportamentali')
                            ->collapsed(false)
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->schema([
                                TextEntry::make('behavioral_notes')
                                    ->hiddenLabel()
                                    ->placeholder('Nessuna nota')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Note sanitarie')
                            ->collapsed(false)
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->schema([
                                TextEntry::make('health_notes')
                                    ->hiddenLabel()
                                    ->placeholder('Nessuna nota')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Grid::make(1)
                    ->schema([
                        Section::make('Proprietario')
                            ->icon(Heroicon::OutlinedUserCircle)
                            ->schema([
                                TextEntry::make('customer.full_name')
                                    ->label('Proprietario')
                                    ->inlineLabel()
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
