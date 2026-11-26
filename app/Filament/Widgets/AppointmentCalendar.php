<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\AppointmentPriceCalculator;
use App\Services\TreatmentService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Peniti\FilamentCalendar\Calendar\Event;
use Peniti\FilamentCalendar\Widgets\Calendar;
use Spatie\OpeningHours\OpeningHours;

class AppointmentCalendar extends Calendar
{
    public array $options = [
        'aspectRatio' => '16/10',
        'headerToolbar' => [
            'left' => 'timeGridDay,timeGridWeek,dayGridMonth',
            'center' => 'title',
            'right' => 'prev,next today,refresh',
        ],
        'initialView' => 'timeGridWeek',
    ];

    protected static string $resource = AppointmentResource::class;

    protected static ?int $sort = 10;

    public function fetchEvents(string $start, string $end): array
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();

        $appointments = Appointment::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('start_time', [Carbon::parse($start), Carbon::parse($end)])
            ->with(['customer', 'pet', 'services'])
            ->get();

        return $appointments->map(fn (Appointment $appointment) => new Event(
            id: (string) $appointment->id,
            title: $appointment->pet->name.' - '.$appointment->customer->fullName,
            start: $appointment->start_time,
            end: $appointment->end_time,
            backgroundColor: $this->getStatusColor($appointment->status),
            editable: $this->isEditable($appointment->status),
            startEditable: $this->isEditable($appointment->status),
        ))->all();
    }

    public function getBusinessHours(): ?OpeningHours
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();

        $spatieHours = collect($tenant->opening_hours)
            ->filter()
            ->mapWithKeys(fn (array $ranges, string $day): array => [
                $day => collect($ranges)
                    ->map(fn (array $range): string => $range['open'].'-'.$range['close'])
                    ->all(),
            ]);

        if ($spatieHours->isEmpty()) {
            return null;
        }

        /** @var array{
         *  monday?: array<array|string>, tuesday?: array<array|string>,
         *  wednesday?: array<array|string>, thursday?: array<array|string>,
         *  friday?: array<array|string>, saturday?: array<array|string>,
         *  sunday?: array<array|string>, exceptions?: array<array<array|string>>
         * } $hours
         */
        $hours = $spatieHours->all();

        return OpeningHours::create($hours);
    }

    public function getSlotDuration(): string
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();

        $minutes = data_get($tenant->settings, 'slot_duration_minutes', 30);

        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }

    protected function calendarActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth(Width::ExtraLarge)
                ->mountUsing(fn (array $arguments, Schema $schema) => $schema->fill([
                    'start_time' => $arguments['start'] ?? null,
                    'end_time' => $arguments['end'] ?? null,
                ])),

            DeleteAction::make(),

            EditAction::make()
                ->slideOver()
                ->modalWidth(Width::ExtraLarge)
                ->using(function (array $data, Model $record) {
                    $serviceIds = $data['services'] ?? [];
                    unset($data['services']);

                    // Defense in depth for the form validation: the status
                    // may only change along a legal state machine transition,
                    // no matter how the submitted data was produced.
                    if ($record instanceof Appointment) {
                        $this->assertLegalStatusTransition($record, $data['status'] ?? null);
                    }

                    /** @var Tenant $tenant */
                    $tenant = Filament::getTenant();

                    $pet = isset($data['pet_id'])
                        ? Pet::where('tenant_id', $tenant->id)->find($data['pet_id'])
                        : ($record instanceof Appointment ? $record->pet : null);

                    if (! empty($serviceIds) && isset($data['start_time'])) {
                        $data['end_time'] = $this->calculateEndTime($data['start_time'], $serviceIds, $pet);
                    }

                    // Services are synced before the update so that, when the same
                    // edit completes the appointment, the generated treatment totals
                    // reflect the new pivot. One transaction keeps them atomic.
                    DB::transaction(function () use ($data, $record, $serviceIds, $pet, $tenant): void {
                        if ($record instanceof Appointment) {
                            // The pre-flight check read the in-memory status:
                            // re-verify on a row lock, so a concurrent request
                            // cannot slip an illegal transition between the
                            // check and the write.
                            $locked = Appointment::query()
                                ->where('tenant_id', $tenant->id)
                                ->whereKey($record->getKey())
                                ->lockForUpdate()
                                ->first();

                            if ($locked === null) {
                                throw new Halt;
                            }

                            $this->assertLegalStatusTransition($locked, $data['status'] ?? null);
                        }

                        if ($serviceIds && $record instanceof Appointment) {
                            $this->syncServicesWithPivotData($record, $serviceIds, $pet);
                        }

                        $record->update($data);
                    });
                })
                ->extraModalFooterActions(function () {
                    $deleteAction = Arr::get($this->cachedActions, 'delete');

                    return array_filter([
                        $deleteAction?->extraAttributes(['class' => 'ml-auto order-last']),
                        ...$this->getTransitionActions(),
                    ]);
                }),

            ViewAction::make()
                ->slideOver()
                ->modalWidth(Width::ExtraLarge)
                ->extraModalFooterActions(function () {
                    $editAction = Arr::get($this->cachedActions, 'edit');

                    return array_filter([
                        $editAction,
                        ...$this->getTransitionActions(),
                    ]);
                }),
        ];
    }

    protected function customCalendarActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Aggiorna')
                ->tooltip('Aggiorna eventi del calendario')
                ->action(fn () => $this->refreshEvents()),
        ];
    }

    protected function save(array $data, string $model): Model|false
    {
        $serviceIds = $data['services'] ?? [];
        unset($data['services']);

        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();

        $pet = isset($data['pet_id'])
            ? Pet::where('tenant_id', $tenant->id)->find($data['pet_id'])
            : null;

        if (! empty($serviceIds) && isset($data['start_time'])) {
            $data['end_time'] = $this->calculateEndTime($data['start_time'], $serviceIds, $pet);
        }

        // Create and pivot sync share one transaction (#80): the create can
        // already fire treatment generation through the observer, and a
        // failed sync must not leave a serviceless appointment behind. Same
        // shape as the edit path above.
        return DB::transaction(function () use ($data, $model, $serviceIds, $pet) {
            $record = parent::save($data, $model);

            if ($record instanceof Appointment && $serviceIds) {
                $this->syncServicesWithPivotData($record, $serviceIds, $pet);

                // If the create landed directly on Completed — the form's
                // state machine blocks it, but save() does not — the observer
                // generated the treatment against the empty pivot, so its
                // totals are zero. The pivot is authoritative now: refresh
                // within the same transaction (#80).
                if ($record->status === AppointmentStatus::Completed) {
                    app(TreatmentService::class)->refreshTotals($record);
                }
            }

            return $record;
        });
    }

    protected function updateRecord(?Model $record, Carbon $start, ?Carbon $end, bool $allDay): void
    {
        // Records already resolve through the tenant-scoped query, but this
        // path has no policy check of its own: keep the guard explicit so a
        // foreign record can never be moved, even if resolution ever changes.
        /** @var Tenant|null $tenant */
        $tenant = Filament::getTenant();

        if ($tenant === null || ! $record instanceof Appointment || $record->tenant_id !== $tenant->id) {
            return;
        }

        $record->update([
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    /**
     * Stop the save path before any write when the submitted status is not
     * legal for the record's current status. Mirrors the form validation,
     * which rejects the same change earlier with an inline field error.
     */
    private function assertLegalStatusTransition(Appointment $record, mixed $status): void
    {
        $target = AppointmentStatus::tryFromState($status);

        if ($target === null || AppointmentStatus::allows($record->status, $target)) {
            return;
        }

        Notification::make()
            ->title('Transizione non consentita')
            ->body("Lo stato dell'appuntamento non può passare da {$record->status->getLabel()} a {$target->getLabel()}.")
            ->danger()
            ->send();

        throw new Halt;
    }

    private function calculateEndTime(string $startTime, array $serviceIds, ?Pet $pet): Carbon
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();

        $services = Service::where('tenant_id', $tenant->id)
            ->whereIn('id', $serviceIds)
            ->get();
        $totalMinutes = AppointmentPriceCalculator::totalDuration($services, $pet);

        return Carbon::parse($startTime)->addMinutes($totalMinutes);
    }

    private function getStatusColor(AppointmentStatus $status): string
    {
        $colorName = $status->getColor();
        $palette = FilamentColor::getColor($colorName);

        return $palette[500] ?? $palette[600] ?? '#6b7280';
    }

    /**
     * @return array<int, Action>
     */
    private function getTransitionActions(): array
    {
        $record = $this->getRecord();
        if (! $record instanceof Appointment) {
            return [];
        }

        /** @var Appointment $record */
        $status = $record->status;
        $actions = [];

        foreach ($status->nextStatuses() as $nextStatus) {
            $actions[] = Action::make($nextStatus->value)
                ->label($nextStatus->getLabel())
                ->color($nextStatus->getColor())
                ->requiresConfirmation()
                ->modalHeading("Cambia stato in {$nextStatus->getLabel()}")
                ->modalDescription("Sei sicuro di voler cambiare lo stato dell'appuntamento in {$nextStatus->getLabel()}?")
                ->action(fn () => $this->transitionStatus($record, $nextStatus));
        }

        return $actions;
    }

    private function isEditable(AppointmentStatus $status): bool
    {
        return ! in_array($status, [AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow], true);
    }

    private function syncServicesWithPivotData(Appointment $appointment, array $serviceIds, ?Pet $pet = null): void
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();

        $services = Service::where('tenant_id', $tenant->id)
            ->whereIn('id', $serviceIds)
            ->get();
        $pet ??= $appointment->pet;

        $pivotData = AppointmentPriceCalculator::buildPivotData($services, $pet);
        $appointment->services()->sync($pivotData);

        // sync() writes rows without touching the relation cache: drop the
        // possibly-loaded (and now stale, e.g. empty when the created observer
        // read it before the sync) collection so later reads re-query. (#80)
        $appointment->unsetRelation('services');
    }

    private function transitionStatus(Appointment $record, AppointmentStatus $nextStatus): void
    {
        // Transition actions bypass the resource policies, so keep the same
        // explicit tenant guard as the drag-and-drop path: a foreign record
        // can never be transitioned, even if its resolution ever changes.
        /** @var Tenant|null $tenant */
        $tenant = Filament::getTenant();

        if ($tenant === null || $record->tenant_id !== $tenant->id) {
            return;
        }

        if (! $record->status->canTransitionTo($nextStatus)) {
            return;
        }

        DB::transaction(function () use ($record, $nextStatus, $tenant): void {
            // Same TOCTOU guard as the edit path: re-verify on a row lock,
            // since the in-memory status may be stale by now.
            $locked = Appointment::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($record->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null || ! $locked->status->canTransitionTo($nextStatus)) {
                return;
            }

            $locked->update(['status' => $nextStatus]);
        });
        $this->refreshEvents();
    }
}
