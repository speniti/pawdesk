<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use App\Enums\Coat;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\AppointmentPriceCalculator;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();

        return $schema
            ->components([
                Section::make('Cliente e Animale')
                    ->icon(Heroicon::OutlinedHeart)
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
                            ->afterStateUpdated(function (Set $set, Get $get, $state): void {
                                self::removeIncompatibleServices($set, $get, $state);
                                self::recalculateEndTime($set, $get);
                            })
                            ->partiallyRenderComponentsAfterStateUpdated(['end_time', 'price_summary'])
                            ->saved(),

                        Select::make('user_id')
                            ->label('Toelettatore')
                            ->options(fn () => $tenant->users()->pluck('name', 'users.id'))
                            ->searchable()
                            ->nullable()
                            ->preload(),
                    ]),

                Section::make('Data e Ora')
                    ->icon(Heroicon::OutlinedClock)
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
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->schema([
                        Select::make('status')
                            ->label('Stato')
                            ->enum(AppointmentStatus::class)
                            ->options(fn (?Appointment $record): array => self::statusOptions($record))
                            ->rule(fn (?Appointment $record): Closure => self::statusTransitionRule($record))
                            ->default(AppointmentStatus::Requested)
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
                            ->options(function (callable $get) use ($tenant): array {
                                $query = Service::where('tenant_id', $tenant->id)
                                    ->where('status', 'active');

                                $coat = self::petCoat($get('pet_id'));

                                if ($coat !== null) {
                                    $query->where(fn ($q) => $q
                                        ->whereNull('coat')
                                        ->orWhere('coat', $coat->value));
                                }

                                return $query->orderBy('name')->pluck('name', 'id')->all();
                            }),

                        TextEntry::make('price_summary')
                            ->label('Riepilogo Costi')
                            ->state(fn (Get $get): string => self::summarizeServices($get('services') ?? [], $get('pet_id')))
                            ->live()
                            ->columnSpanFull(),

                        Textarea::make('internal_notes')
                            ->label('Note interne')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Keep only services compatible with the given coat, still constrained
     * to the current tenant. Pets without a coat see every service.
     *
     * @param  array<int, int|string>  $serviceIds
     * @return array<int, int|string>
     */
    private static function compatibleServiceIds(array $serviceIds, ?Coat $coat): array
    {
        if ($coat === null) {
            return $serviceIds;
        }

        return self::scopedServices($serviceIds)
            ->filter(fn (Service $service) => $service->coat === null || $service->coat === $coat)
            ->pluck('id')
            ->all();
    }

    private static function pet($petId): ?Pet
    {
        if ($petId === null) {
            return null;
        }

        /** @var Tenant|null $tenant */
        $tenant = Filament::getTenant();

        return $tenant === null ? null : Pet::where('tenant_id', $tenant->id)->find($petId);
    }

    private static function petCoat($petId): ?Coat
    {
        return self::pet($petId)?->coat;
    }

    private static function recalculateEndTime(Set $set, Get $get): void
    {
        $start = $get('start_time');
        $serviceIds = $get('services');

        if (! $start || empty($serviceIds)) {
            $set('end_time', null);

            return;
        }

        $pet = self::pet($get('pet_id'));
        $services = self::scopedServices($serviceIds);
        $totalMinutes = AppointmentPriceCalculator::totalDuration($services, $pet);

        $set('end_time', Carbon::parse($start)->addMinutes($totalMinutes));
    }

    private static function removeIncompatibleServices(Set $set, Get $get, $petId): void
    {
        $serviceIds = $get('services') ?? [];

        if (empty($serviceIds)) {
            return;
        }

        $coat = self::petCoat($petId);

        // Pets without a coat see every service, so nothing has to be pruned.
        if ($coat === null) {
            return;
        }

        $set('services', self::compatibleServiceIds($serviceIds, $coat));
    }

    /**
     * Resolve services by id, constrained to the current tenant. Fails
     * closed: without a tenant in context, no services are returned.
     *
     * @param  array<int, int|string>  $serviceIds
     * @return Collection<int, Service>
     */
    private static function scopedServices(array $serviceIds): Collection
    {
        /** @var Tenant|null $tenant */
        $tenant = Filament::getTenant();

        if ($tenant === null) {
            return collect();
        }

        return Service::where('tenant_id', $tenant->id)->whereIn('id', $serviceIds)->get();
    }

    /**
     * Statuses offered by the form: on creation only the machine's initial
     * statuses; while editing the current status plus the reachable ones,
     * so the record can always be saved without changing it.
     *
     * @return array<string, string>
     */
    private static function statusOptions(?Appointment $record): array
    {
        $statuses = $record === null
            ? AppointmentStatus::initialStatuses()
            : [$record->status, ...$record->status->nextStatuses()];

        return collect($statuses)
            ->mapWithKeys(fn (AppointmentStatus $status): array => [
                $status->value => $status->getLabel(),
            ])
            ->all();
    }

    /**
     * Server-side enforcement of the same rule the options express, so a
     * forged submission cannot skip the state machine.
     */
    private static function statusTransitionRule(?Appointment $record): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            $target = AppointmentStatus::tryFromState($value);

            if ($target === null) {
                return; // Non-enum values are rejected by the enum rule.
            }

            $allowed = $record === null
                ? in_array($target, AppointmentStatus::initialStatuses(), true)
                : ($target === $record->status || $record->status->canTransitionTo($target));

            if (! $allowed) {
                $fail($record === null
                    ? 'Lo stato selezionato non è ammesso alla creazione di un appuntamento.'
                    : "Lo stato {$target->getLabel()} non è raggiungibile da {$record->status->getLabel()}.");
            }
        };
    }

    /**
     * @param  array<int, int|string>  $serviceIds
     */
    private static function summarizeServices(array $serviceIds, $petId): string
    {
        $pet = self::pet($petId);

        if (empty($serviceIds)) {
            return '—';
        }

        $services = self::scopedServices($serviceIds);

        if ($services->isEmpty()) {
            return '—';
        }

        $totalPrice = 0;
        $totalDuration = 0;
        $lines = [];

        foreach ($services as $service) {
            $price = AppointmentPriceCalculator::resolvePrice($service, $pet);
            $duration = AppointmentPriceCalculator::resolveDuration($service, $pet);
            $totalPrice += $price;
            $totalDuration += $duration;
            $lines[] = "{$service->name}: €".number_format($price / 100, 2)." ({$duration} min)";
        }

        $lines[] = 'Totale: €'.number_format($totalPrice / 100, 2)." — Durata: {$totalDuration} min";

        return implode(' · ', $lines);
    }
}
