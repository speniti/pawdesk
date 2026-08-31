<?php

declare(strict_types=1);

namespace App\Filament\Resources\Treatments\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TreatmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dettagli Trattamento')
                    ->schema([
                        TextInput::make('actual_duration_minutes')
                            ->label('Durata effettiva (minuti)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(480)
                            ->suffix('min'),

                        TextInput::make('final_price')
                            ->label('Prezzo finale (€)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->formatStateUsing(fn ($state): ?float => $state ? $state / 100 : null)
                            ->dehydrateStateUsing(fn ($state): ?int => $state !== null ? (int) round($state * 100) : null)
                            ->suffix('€'),

                        Toggle::make('visible_to_customer')
                            ->label('Visibile al cliente'),
                    ])
                    ->columns(3),

                Section::make('Note')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Note trattamento')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('products_used')
                            ->label('Prodotti utilizzati')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
