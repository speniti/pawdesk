<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditTenantSettings extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Impostazioni';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Orari di apertura')
                    ->description('Definisci gli orari di apertura per ogni giorno della settimana. Lascia vuoto per i giorni di chiusura.')
                    ->schema($this->openingHoursSections())
                    ->columns(2)
                    ->collapsible(),

                Section::make('Configurazione slot')
                    ->description('Imposta la durata degli slot di prenotazione e il buffer tra appuntamenti.')
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
                    ->columns(2),

                Section::make('Credenziali notifiche')
                    ->description('Configura le credenziali per l\'invio di email tramite Mailgun e SMS tramite Vonage.')
                    ->schema([
                        TextInput::make('notification_settings.mailgun_api_key')
                            ->label('Mailgun - API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        TextInput::make('notification_settings.mailgun_domain')
                            ->label('Mailgun - Dominio')
                            ->maxLength(255),

                        TextInput::make('notification_settings.vonage_api_key')
                            ->label('Vonage - API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        TextInput::make('notification_settings.vonage_api_secret')
                            ->label('Vonage - API Secret')
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        TextInput::make('notification_settings.vonage_sms_sender_id')
                            ->label('Vonage - Mittente SMS')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['notification_settings'] ??= [];

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Impostazioni salvate';
    }

    /** @return Section[] */
    private function openingHoursSections(): array
    {
        $days = [
            'monday' => 'Lunedì',
            'tuesday' => 'Martedì',
            'wednesday' => 'Mercoledì',
            'thursday' => 'Giovedì',
            'friday' => 'Venerdì',
            'saturday' => 'Sabato',
            'sunday' => 'Domenica',
        ];

        $sections = [];
        foreach ($days as $key => $label) {
            $sections[] = Section::make($label)
                ->schema([
                    Repeater::make("opening_hours.{$key}")
                        ->label('')
                        ->schema([
                            TimePicker::make('open')
                                ->label('Apertura')
                                ->required()
                                ->seconds(false)
                                ->format('H:i'),

                            TimePicker::make('close')
                                ->label('Chiusura')
                                ->required()
                                ->seconds(false)
                                ->format('H:i')
                                ->after('open'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Aggiungi fascia oraria')
                        ->reorderable(false)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => isset($state['open'], $state['close']) ? "{$state['open']} - {$state['close']}" : null),
                ])
                ->columns(1);
        }

        return $sections;
    }
}
