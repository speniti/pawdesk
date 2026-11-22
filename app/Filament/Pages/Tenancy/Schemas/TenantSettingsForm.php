<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy\Schemas;

use App\Enums\InpsMode;
use App\Filament\Pages\Tenancy\Schemas\Components\OpeningHoursDaySection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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

                Section::make('Fiscalità (regime forfettario)')
                    ->description('Parametri per la stima di imposte e contributi nella pagina Fatturato e tasse.')
                    ->aside()
                    ->schema([
                        TextInput::make('settings.tax_coefficient')
                            ->label('Coefficiente di redditività (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            // `default()` never applies here: this form is always
                            // filled with existing tenant data, so hydrate the
                            // fallback explicitly (78% = ATECO 96.09 toelettatura).
                            ->formatStateUsing(fn ($state): int => filled($state) ? (int) $state : 78)
                            ->suffix('%'),

                        TextInput::make('settings.tax_rate')
                            ->label('Aliquota imposta sostitutiva (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->formatStateUsing(fn ($state): int => filled($state) ? (int) $state : 15)
                            ->suffix('%')
                            ->helperText('15% ordinaria, 5% per nuove attività nei primi 5 anni.'),

                        Select::make('settings.inps_mode')
                            ->label('Regime contributivo INPS')
                            ->options(InpsMode::class)
                            ->placeholder('Seleziona il regime')
                            ->live(),

                        TextInput::make('settings.inps_fixed_amount')
                            ->label('Contributi fissi annui (€)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('€')
                            ->visible(fn (Get $get): bool => self::inpsModeIs($get, InpsMode::Fixed))
                            ->required(fn (Get $get): bool => self::inpsModeIs($get, InpsMode::Fixed)),

                        TextInput::make('settings.inps_percentage')
                            ->label('Aliquota INPS (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->visible(fn (Get $get): bool => self::inpsModeIs($get, InpsMode::Percentage))
                            ->required(fn (Get $get): bool => self::inpsModeIs($get, InpsMode::Percentage)),
                    ])
                    ->columns(1)
                    ->collapsible(),

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

                        Select::make('notification_settings.mailgun_region')
                            ->label('Regione Mailgun')
                            ->options([
                                'us' => 'Stati Uniti',
                                'eu' => 'Europa',
                            ])
                            ->default('us')
                            ->selectablePlaceholder(false),

                        TextInput::make('notification_settings.mail_from_address')
                            ->label('Indirizzo mittente')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('notification_settings.mail_from_name')
                            ->label('Nome mittente')
                            ->maxLength(255),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),

                Section::make('Dati del titolare del trattamento')
                    ->description('Questi dati compaiono nell\'informativa sulla privacy inviata ai clienti, come richiesto dall\'art. 13 del Regolamento UE 2016/679 (GDPR).')
                    ->aside()
                    ->schema([
                        TextInput::make('settings.privacy_business_name')
                            ->label('Ragione sociale')
                            ->maxLength(255),

                        TextInput::make('settings.privacy_owner_name')
                            ->label('Titolare / Legale rappresentativo')
                            ->maxLength(255),

                        TextInput::make('settings.privacy_vat_number')
                            ->label('Partita IVA')
                            ->maxLength(20),

                        TextInput::make('settings.privacy_business_address')
                            ->label('Sede legale')
                            ->maxLength(255),

                        TextInput::make('settings.privacy_contact_email')
                            ->label('Email contatto privacy')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('settings.privacy_contact_phone')
                            ->label('Telefono')
                            ->tel()
                            ->maxLength(30),
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
                            ->requiredWith('notification_settings.vonage_api_key')
                            ->maxLength(11)
                            ->helperText('Mittente visualizzato dal cliente: 11 caratteri max, es. PawDesk.'),
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

    /**
     * The select state hydrates to an InpsMode instance, but Livewire requests
     * may still carry the raw string value: normalize both before comparing.
     */
    private static function inpsModeIs(Get $get, InpsMode $mode): bool
    {
        $state = $get('settings.inps_mode');

        $state = $state instanceof InpsMode
            ? $state
            : InpsMode::tryFrom((string) $state);

        return $state === $mode;
    }
}
