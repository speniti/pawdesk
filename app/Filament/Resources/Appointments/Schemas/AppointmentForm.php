<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\AppointmentPriceCalculator;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

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
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculateEndTime($set, $get))
                            ->partiallyRenderComponentsAfterStateUpdated(['end_time'])
                            ->default(now()),

                        DateTimePicker::make('end_time')
                            ->label('Fine')
                            ->seconds(false)
                            ->disabled()
                            ->saved(),
                    ])
                    ->columns(2),

                Section::make('Dettagli')
                    ->schema([
                        Select::make('status')
                            ->label('Stato')
                            ->options(AppointmentStatus::class)
                            ->default(AppointmentStatus::Requested)
                            ->disabled()
                            ->saved()
                            ->required(),

                        Select::make('services')
                            ->label('Servizi')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculateEndTime($set, $get))
                            ->partiallyRenderComponentsAfterStateUpdated(['end_time', 'price_summary'])
                            ->options(fn () => Service::where('tenant_id', $tenant->id)
                                ->where('status', 'active')
                                ->pluck('name', 'id')),

                        TextEntry::make('price_summary')
                            ->label('Riepilogo Costi')
                            ->state(function (Get $get): string {
                                $serviceIds = $get('services') ?? [];
                                $petId = $get('pet_id');

                                if (empty($serviceIds)) {
                                    return '—';
                                }

                                $services = Service::whereIn('id', $serviceIds)->get();
                                $petSize = $petId ? Pet::find($petId)?->size : null;

                                $totalPrice = 0;
                                $totalDuration = 0;
                                $lines = [];

                                foreach ($services as $service) {
                                    $price = AppointmentPriceCalculator::resolvePrice($service, $petSize);
                                    $totalPrice += $price;
                                    $totalDuration += $service->duration_minutes;
                                    $lines[] = "{$service->name}: €".number_format($price / 100, 2)." ({$service->duration_minutes} min)";
                                }

                                $lines[] = 'Totale: €'.number_format($totalPrice / 100, 2)." — Durata: {$totalDuration} min";

                                return implode(' · ', $lines);
                            })
                            ->live()
                            ->columnSpanFull(),

                        Textarea::make('internal_notes')
                            ->label('Note interne')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function recalculateEndTime(Set $set, Get $get): void
    {
        $start = $get('start_time');
        $serviceIds = $get('services');

        if (! $start || empty($serviceIds)) {
            $set('end_time', null);

            return;
        }

        $totalMinutes = Service::whereIn('id', $serviceIds)->sum('duration_minutes');
        $set('end_time', Carbon::parse($start)->addMinutes($totalMinutes));
    }
}
