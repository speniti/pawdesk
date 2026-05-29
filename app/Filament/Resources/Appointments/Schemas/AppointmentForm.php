<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();

        return $schema
            ->components([
                Section::make('Cliente e Animale')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Customer $record): string => $record->fullName)
                            ->searchable(['first_name', 'last_name'])
                            ->required()
                            ->live()
                            ->preload(),

                        Select::make('pet_id')
                            ->label('Animale')
                            ->options(fn (callable $get) => Pet::where('customer_id', $get('customer_id'))
                                ->where('tenant_id', $tenant->id)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->visible(fn (callable $get): bool => filled($get('customer_id')))
                            ->disabled(fn (callable $get): bool => blank($get('customer_id')))
                            ->saved(),

                        Select::make('user_id')
                            ->label('Toelettatore')
                            ->options(fn () => $tenant->users()->pluck('name', 'users.id'))
                            ->searchable()
                            ->nullable()
                            ->preload(),
                    ])
                    ->columns(3),

                Section::make('Data e Ora')
                    ->schema([
                        DateTimePicker::make('start_time')
                            ->label('Inizio')
                            ->required()
                            ->seconds(false)
                            ->live()
                            ->default(now()),

                        DateTimePicker::make('end_time')
                            ->label('Fine')
                            ->required()
                            ->seconds(false)
                            ->after('start_time'),
                    ])
                    ->columns(2),

                Section::make('Dettagli')
                    ->schema([
                        Select::make('status')
                            ->label('Stato')
                            ->options(AppointmentStatus::class)
                            ->default(AppointmentStatus::Requested)
                            ->required(),

                        Select::make('services')
                            ->label('Servizi')
                            ->relationship('services', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => Service::where('tenant_id', $tenant->id)
                                ->where('status', 'active')
                                ->pluck('name', 'id')),

                        Textarea::make('internal_notes')
                            ->label('Note interne')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
