<?php

declare(strict_types=1);

namespace App\Filament\Resources\Services\Schemas;

use App\Enums\Coat;
use App\Enums\ServiceCategory;
use App\Enums\ServiceStatus;
use App\Enums\Size;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informazioni generali')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Descrizione')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),

                        Select::make('category')
                            ->label('Categoria')
                            ->options(ServiceCategory::class)
                            ->required(),

                        Select::make('coat')
                            ->label('Tipo di manto')
                            ->options(Coat::class)
                            ->nullable(),

                        TextInput::make('duration_minutes')
                            ->label('Durata base (minuti)')
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

                        ToggleButtons::make('combinable')
                            ->label('Combinabile con altri servizi')
                            ->boolean()
                            ->grouped()
                            ->default(true),
                    ])
                    ->columns(3),

                Section::make('Prezzi e durate per combinazione')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->description('Prezzo e durata applicati solo con corrispondenza esatta di taglia e tipo di pelo. In tutti gli altri casi vengono usati prezzo base e durata base.')
                    ->schema([
                        Repeater::make('variations')
                            ->hiddenLabel()
                            ->schema([
                                Select::make('size')
                                    ->label('Taglia')
                                    ->options(Size::class)
                                    ->required(),

                                Select::make('coat')
                                    ->label('Tipo di pelo')
                                    ->options(Coat::class)
                                    ->required(),

                                TextInput::make('price')
                                    ->label('Prezzo (€)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->formatStateUsing(fn ($state): ?float => $state ? $state / 100 : null)
                                    ->dehydrateStateUsing(fn ($state): ?int => $state !== null ? (int) round($state * 100) : null)
                                    ->suffix('€'),

                                TextInput::make('duration_minutes')
                                    ->label('Durata (minuti)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(480),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('Aggiungi una combinazione')
                            ->rules([
                                static fn (): Closure => static function (string $attribute, $value, Closure $fail): void {
                                    $pairs = collect($value ?? [])
                                        ->map(fn (array $item): string => implode('|', [
                                            $item['size'] ?? '',
                                            $item['coat'] ?? '',
                                        ]))
                                        ->filter(fn (string $pair): bool => $pair !== '|');

                                    if ($pairs->duplicates()->isNotEmpty()) {
                                        $fail('Alcune combinazioni taglia e tipo di pelo sono duplicate.');
                                    }
                                },
                            ])
                            ->itemLabel(function (array $state): ?string {
                                $size = $state['size'] instanceof Size
                                    ? $state['size']->getLabel()
                                    : ($state['size'] ?? null);
                                $coat = $state['coat'] instanceof Coat
                                    ? $state['coat']->getLabel()
                                    : ($state['coat'] ?? null);

                                if ($size === null && $coat === null) {
                                    return null;
                                }

                                return collect([$size, $coat])->filter()->implode(' · ');
                            }),
                    ])
                    ->columns(1),
            ]);
    }
}
