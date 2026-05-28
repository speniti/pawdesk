<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(3)
                    ->schema([
                        Section::make('Appuntamento')
                            ->schema([
                                TextEntry::make('pet.name')
                                    ->label('Animale'),

                                TextEntry::make('customer.fullName')
                                    ->label('Cliente'),

                                TextEntry::make('user.name')
                                    ->label('Toelettatore')
                                    ->placeholder('Non assegnato'),

                                TextEntry::make('start_time')
                                    ->label('Inizio')
                                    ->dateTime(),

                                TextEntry::make('end_time')
                                    ->label('Fine')
                                    ->dateTime(),

                                TextEntry::make('status')
                                    ->label('Stato')
                                    ->badge(),
                            ])
                            ->columnSpan(2),

                        Section::make('Dettagli')
                            ->schema([
                                TextEntry::make('services.name')
                                    ->label('Servizi')
                                    ->badge()
                                    ->separator(','),

                                TextEntry::make('internal_notes')
                                    ->label('Note interne')
                                    ->placeholder('Nessuna nota')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
