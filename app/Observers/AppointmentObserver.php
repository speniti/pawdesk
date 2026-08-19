<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentCompletedNotification;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\AppointmentRequestedNotification;
use App\Notifications\BaseAppointmentNotification;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class AppointmentObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Appointment $appointment): void
    {
        $this->dispatch(new AppointmentRequestedNotification($appointment), $appointment);
    }

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
