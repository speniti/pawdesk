<?php

declare(strict_types=1);

namespace App\Filament\Resources\Treatments\Schemas;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Pets\PetResource;
use App\Models\Treatment;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TreatmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Dettagli Trattamento')
                            ->columns(2)
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->schema([
                                TextEntry::make('actual_duration_minutes')
                                    ->label('Durata effettiva')
                                    ->suffix(' min'),

                                TextEntry::make('final_price')
                                    ->label('Prezzo finale')
                                    ->formatStateUsing(fn (int $state): string => '€'.number_format($state / 100, 2)),

                                TextEntry::make('services')
                                    ->label('Servizi eseguiti')
                                    ->state(function (Treatment $record): string {
                                        $services = $record->appointment->loadMissing('services')->services;

                                        return $services
                                            ->map(static fn ($service): string => "{$service->name}: €"
                                                .number_format($service->pivot->applied_price / 100, 2)
                                                ." ({$service->pivot->duration_minutes} min)")
                                            ->implode(' · ') ?: '—';
                                    })
                                    ->columnSpanFull(),

                                TextEntry::make('notes')
                                    ->label('Note trattamento')
                                    ->placeholder('Nessuna nota')
                                    ->columnSpanFull(),

                                TextEntry::make('products_used')
                                    ->label('Prodotti utilizzati')
                                    ->placeholder('Nessun prodotto')
                                    ->columnSpanFull(),

                                IconEntry::make('visible_to_customer')
                                    ->label('Visibile al cliente')
                                    ->boolean(),
                            ]),
                    ]),

                Grid::make(1)
                    ->schema([
                        Section::make('Contesto')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->inlineLabel()
                            ->schema([
                                TextEntry::make('appointment.start_time')
                                    ->label('Appuntamento')
                                    ->dateTime('d/m/Y H:i'),

                                TextEntry::make('pet.name')
                                    ->label('Animale')
                                    ->alignEnd()
                                    ->url(fn (Treatment $record): string => PetResource::getUrl('view', ['record' => $record->pet_id])),

                                TextEntry::make('customer.full_name')
                                    ->label('Cliente')
                                    ->alignEnd()
                                    ->url(fn (Treatment $record): string => CustomerResource::getUrl('view', ['record' => $record->customer_id])),
                            ]),
                    ]),
            ]);
    }
}
