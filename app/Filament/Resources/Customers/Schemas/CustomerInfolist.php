<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Recapiti')
                            ->columns(2)
                            ->icon(Heroicon::OutlinedIdentification)
                            ->schema([
                                TextEntry::make('email')
                                    ->label('Email')
                                    ->size(TextSize::Large)
                                    ->copyable(),

                                TextEntry::make('phone')
                                    ->size(TextSize::Large)
                                    ->label('Telefono'),

                                TextEntry::make('address')
                                    ->label('Indirizzo')
                                    ->size(TextSize::Large)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Note')
                            ->collapsed()
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->schema([
                                TextEntry::make('notes')
                                    ->hiddenLabel()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Grid::make(1)
                    ->schema([
                        Section::make('Comunicazione')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->schema([
                                TextEntry::make('preferred_channel')
                                    ->label('Canale preferito')
                                    ->inlineLabel()
                                    ->alignEnd()
                                    ->badge(),
                            ]),

                        Section::make('Privacy e consensi')
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->inlineLabel()
                            ->schema([
                                IconEntry::make('gdpr_policy_sent_at')
                                    ->label('Informativa privacy')
                                    ->state(static fn (Customer $record) => ! is_null($record->gdpr_policy_sent_at))
                                    ->alignEnd()
                                    ->boolean(),

                                IconEntry::make('marketing_consent_at')
                                    ->label('Consenso marketing')
                                    ->state(static fn (Customer $record) => ! is_null($record->marketing_consent_at))
                                    ->alignEnd()
                                    ->boolean(),
                            ]),
                    ]),
            ]);
    }
}
