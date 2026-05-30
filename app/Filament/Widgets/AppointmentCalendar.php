<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\AppointmentPriceCalculator;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
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

                    if (! empty($serviceIds) && isset($data['start_time'])) {
                        $data['end_time'] = $this->calculateEndTime($data['start_time'], $serviceIds);
                    }

                    $record->update($data);

                    if ($serviceIds && $record instanceof Appointment) {
                        $this->syncServicesWithPivotData($record, $serviceIds);
                    }
                })
                ->extraModalFooterActions(function () {
                    $deleteAction = Arr::get($this->cachedActions, 'delete');

                    return [
                        $deleteAction?->extraAttributes(['class' => 'ml-auto order-last']),
                        ...$this->getTransitionActions(),
                    ];
                }),

            ViewAction::make()
                ->slideOver()
                ->modalWidth(Width::ExtraLarge)
                ->extraModalFooterActions(function () {
                    $editAction = Arr::get($this->cachedActions, 'edit');

                    return [
                        $editAction,
                        ...$this->getTransitionActions(),
                    ];
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

        if (! empty($serviceIds) && isset($data['start_time'])) {
            $data['end_time'] = $this->calculateEndTime($data['start_time'], $serviceIds);
        }

        $record = parent::save($data, $model);

        if ($record instanceof Appointment && $serviceIds) {
            $this->syncServicesWithPivotData($record, $serviceIds);
        }

        return $record;
    }

    protected function updateRecord(?Model $record, Carbon $start, ?Carbon $end, bool $allDay): void
    {
        $record?->update([
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    private function calculateEndTime(string $startTime, array $serviceIds): Carbon
    {
        $totalMinutes = Service::whereIn('id', $serviceIds)->sum('duration_minutes');

        return Carbon::parse($startTime)->addMinutes((int) $totalMinutes);
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
                ->action(function () use ($record, $status, $nextStatus) {
                    if (! $status->canTransitionTo($nextStatus)) {
                        return;
                    }
                    $record->update(['status' => $nextStatus]);
                    $this->refreshEvents();
                });
        }

        return $actions;
    }

    private function isEditable(AppointmentStatus $status): bool
    {
        return ! in_array($status, [AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow], true);
    }

    private function syncServicesWithPivotData(Appointment $appointment, array $serviceIds): void
    {
        $services = Service::whereIn('id', $serviceIds)->get();
        $petSize = $appointment->pet?->size;

        $pivotData = AppointmentPriceCalculator::buildPivotData($services, $petSize);
        $appointment->services()->sync($pivotData);
    }
}
