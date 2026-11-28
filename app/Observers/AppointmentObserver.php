<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentCompletedNotification;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\BaseAppointmentNotification;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Notifications are transition-only by decision (#152): the customer is told
 * about a completed appointment because the visit just happened. An
 * appointment that arrives already Completed — backfill of past visits, data
 * fixes — must stay silent, so there is deliberately no created() handler;
 * AppointmentObserverTest pins this contract.
 *
 * Note for a future created() handler: during created the model's changes
 * array is never synced, so wasChanged() is false for every attribute while
 * isDirty() is true — neither tells a backfill apart from an update. The
 * no-notification contract lives in the guard test, not in model state.
 */
class AppointmentObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(Appointment $appointment): void
    {
        if (! $appointment->wasChanged('status')) {
            return;
        }

        $notification = match ($appointment->status) {
            AppointmentStatus::Confirmed => new AppointmentConfirmedNotification($appointment),
            AppointmentStatus::Cancelled => new AppointmentCancelledNotification($appointment),
            AppointmentStatus::Completed => new AppointmentCompletedNotification($appointment),
            default => null,
        };

        if ($notification === null) {
            return;
        }

        $this->dispatch($notification, $appointment);
    }

    private function dispatch(BaseAppointmentNotification $notification, Appointment $appointment): void
    {
        // loadMissing() avoids lazy loading violations with preventLazyLoading()
        // enabled outside production; tenant is needed for channel resolution.
        $customer = $appointment->loadMissing('customer.tenant')->customer;

        $notification->createLog($customer);
        $customer->notify($notification);
    }
}
