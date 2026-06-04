<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentCompletedNotification;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\AppointmentRequestedNotification;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class AppointmentObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Appointment $appointment): void
    {
        $customer = $appointment->customer;

        $notification = new AppointmentRequestedNotification($appointment);
        $notification->createLog($customer);
        $customer->notify($notification);
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

        $customer = $appointment->customer;
        $notification->createLog($customer);
        $customer->notify($notification);
    }
}
