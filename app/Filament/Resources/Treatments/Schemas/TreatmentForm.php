<?php

declare(strict_types=1);

namespace App\Filament\Resources\Treatments\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TreatmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Dettagli Trattamento')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
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

                        ToggleButtons::make('visible_to_customer')
                            ->label('Visibile al cliente')
                            ->boolean()
                            ->grouped()
                            ->default(true),
                    ])
                    ->columns(3),

                Section::make('Note')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->schema([
                        Textarea::make('notes')
                            ->hiddenLabel()
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),

                    ]),

                Section::make('Prodotti utilizzati')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->afterHeader([
                        Icon::make(Heroicon::OutlinedInformationCircle),
                        Text::make('Indica un prodotto per riga.'),
                    ])
                    ->schema([
                        Textarea::make('products_used')
                            ->hiddenLabel()
                            ->maxLength(1000)
                            ->rows(3)
                            ->placeholder('Indica i prodotti utilizzati')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
