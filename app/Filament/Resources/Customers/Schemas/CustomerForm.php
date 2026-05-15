<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;
use Peniti\FilamentMapbox\Forms\Fields\Geocoder;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Dati anagrafici')
                    ->columns(2)
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('last_name')
                            ->label('Cognome')
                            ->required()
                            ->maxLength(255),
                    ]),

                Section::make('Recapiti')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule) => $rule->where('tenant_id', Filament::getTenant()->getKey())
                            ),

                        TextInput::make('phone')
                            ->label('Telefono')
                            ->tel()
                            ->required()
                            ->maxLength(50),

                        Geocoder::make('address')
                            ->label('Indirizzo')
                            ->country('it')
                            ->prefixIcon(Heroicon::OutlinedMap)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Note')
                    ->collapsed()
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->schema([
                        Textarea::make('notes')
                            ->hiddenLabel(),
                    ]),
            ]);
    }
}
