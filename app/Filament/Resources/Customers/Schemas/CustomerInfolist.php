<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dati anagrafici')
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Nome'),

                        TextEntry::make('email')
                            ->label('Email')
                            ->copyable(),

                        TextEntry::make('phone')
                            ->label('Telefono')
                            ->copyable(),

                        TextEntry::make('address')
                            ->label('Indirizzo')
                            ->placeholder('Non specificato'),
                    ])
                    ->columns(2),

                Section::make('Comunicazione')
                    ->schema([
                        TextEntry::make('preferred_channel')
                            ->label('Canale preferito')
                            ->badge(),
                    ]),

                Section::make('Privacy e consensi')
                    ->schema([
                        TextEntry::make('gdpr_policy_sent_at')
                            ->label('Informativa privacy inviata il')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non ancora inviata'),

                        IconEntry::make('marketing_consent_at')
                            ->label('Consenso marketing')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('Preferenze e note')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Note')
                            ->placeholder('Nessuna nota')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
