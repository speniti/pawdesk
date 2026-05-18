<?php

declare(strict_types=1);

namespace App\Filament\Resources\Services\Schemas;

use App\Enums\Coat;
use App\Enums\ServiceCategory;
use App\Enums\ServiceStatus;
use App\Enums\Size;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informazioni generali')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('Descrizione')
                            ->maxLength(1000)
                            ->rows(3),

                        Select::make('category')
                            ->label('Categoria')
                            ->options(ServiceCategory::class)
                            ->required(),

                        Select::make('coat')
                            ->label('Tipo di manto')
                            ->options(Coat::class)
                            ->nullable(),

                        TextInput::make('duration_minutes')
                            ->label('Durata (minuti)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(480)
                            ->default(60),

                        TextInput::make('base_price')
                            ->label('Prezzo base (€)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->formatStateUsing(fn ($state): ?float => $state ? $state / 100 : null)
                            ->dehydrateStateUsing(fn ($state): ?int => $state !== null ? (int) round($state * 100) : null)
                            ->suffix('€'),

                        Select::make('status')
                            ->label('Stato')
                            ->options(ServiceStatus::class)
                            ->default(ServiceStatus::Active)
                            ->required()
                            ->live(),
                    ])
                    ->columns(2),

                Section::make('Opzioni')
                    ->schema([
                        TextInput::make('combinable')
                            ->label('Combinabile con altri servizi')
                            ->default(true),
                    ])
                    ->columns(1),

                Section::make('Prezzi per taglia')
                    ->description('Definisci prezzi specifici per ogni taglia. Se non specificato, verrà utilizzato il prezzo base.')
                    ->schema([
                        Repeater::make('size_prices')
                            ->label('')
                            ->schema([
                                Select::make('size')
                                    ->label('Taglia')
                                    ->options(Size::class)
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                TextInput::make('price')
                                    ->label('Prezzo (€)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->formatStateUsing(fn ($state): ?float => $state ? $state / 100 : null)
                                    ->dehydrateStateUsing(fn ($state): ?int => $state !== null ? (int) round($state * 100) : null)
                                    ->suffix('€'),
                            ])
                            ->columns(2)
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => isset($state['size']) && $state['size'] instanceof Size ? $state['size']->getLabel() : ($state['size'] ?? null)),
                    ])
                    ->columns(1),
            ]);
    }
}
