<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy\Schemas;

use App\Filament\Pages\Tenancy\Schemas\Components\OpeningHoursDaySection;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TenantSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Orari di apertura')
                    ->description('Definisci gli orari di apertura per ogni giorno della settimana. Lascia vuoto per i giorni di chiusura.')
                    ->aside()
                    ->schema(
                        collect(self::dayLabels())
                            ->map(fn (string $label, string $key): Section => OpeningHoursDaySection::make($key, $label))
                            ->values()
                            ->all()
                    )
                    ->columns(1)
                    ->collapsible(),

                Section::make('Configurazione slot')
                    ->description('Imposta la durata degli slot di prenotazione e il buffer tra appuntamenti.')
                    ->aside()
                    ->schema([
                        TextInput::make('settings.slot_duration_minutes')
                            ->label('Durata slot (minuti)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(480)
                            ->default(30)
                            ->suffix('min'),

                        TextInput::make('settings.buffer_minutes')
                            ->label('Buffer tra appuntamenti (minuti)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(120)
                            ->default(15)
                            ->suffix('min'),
                    ])
                    ->columns(1),

                Section::make('Email (Mailgun)')
                    ->description('Configura le credenziali per l\'invio di email tramite Mailgun.')
                    ->aside()
                    ->schema([
                        TextInput::make('notification_settings.mailgun_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        TextInput::make('notification_settings.mailgun_domain')
                            ->label('Dominio')
                            ->maxLength(255),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),

                Section::make('SMS (Vonage)')
                    ->description('Configura le credenziali per l\'invio di SMS tramite Vonage.')
                    ->aside()
                    ->schema([
                        TextInput::make('notification_settings.vonage_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        TextInput::make('notification_settings.vonage_api_secret')
                            ->label('API Secret')
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        TextInput::make('notification_settings.vonage_sms_sender_id')
                            ->label('Mittente SMS')
                            ->maxLength(255),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /** @return array<string, string> */
    private static function dayLabels(): array
    {
        return [
            'monday' => 'Lunedì',
            'tuesday' => 'Martedì',
            'wednesday' => 'Mercoledì',
            'thursday' => 'Giovedì',
            'friday' => 'Venerdì',
            'saturday' => 'Sabato',
            'sunday' => 'Domenica',
        ];
    }
}
