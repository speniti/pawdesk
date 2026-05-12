<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\PreferredChannel;
use App\Models\Customer;
use Filament\Facades\Filament;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;
use Peniti\FilamentMapbox\Forms\Fields\Geocoder;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dati anagrafici')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('last_name')
                            ->label('Cognome')
                            ->required()
                            ->maxLength(255),

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
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Comunicazione')
                    ->schema([
                                                    Select::make('preferred_channel')
                                                        ->label('Canale preferito')
                                                        ->options(PreferredChannel::class)
                                                        ->default(PreferredChannel::Email)
                                                        ->required(),
                                                ]),

                Section::make('Privacy e consensi')
                    ->schema([
                                                    Placeholder::make('gdpr_policy_sent_at')
                                                        ->label('Informativa privacy inviata il')
                                                        ->content(fn (?Customer $record): string => $record?->gdpr_policy_sent_at?->format('d/m/Y H:i') ?? 'Non ancora inviata')
                                                        ->hiddenOn('create'),

                                                    Toggle::make('marketing_consent')
                                                        ->label('Consenso marketing')
                                                        ->formatStateUsing(fn (?Customer $record): bool => $record?->marketing_consent_at !== null),
                                                ]),

                Section::make('Preferenze e note')
                    ->schema([
                                                    KeyValue::make('preferences')
                                                        ->label('Preferenze')
                                                        ->keyLabel('Chiave')
                                                        ->valueLabel('Valore'),

                                                    Textarea::make('notes')
                                                        ->label('Note')
                                                        ->maxLength(1000)
                                                        ->columnSpanFull(),
                                                ]),
            ]);
    }
}
