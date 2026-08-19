<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('appointments:send-reminders')]
#[Description('Send 24h and 1h reminders for confirmed appointments, skipping already dispatched ones')]
class SendAppointmentRemindersCommand extends Command
{
    /**
     * Reminder offsets mapped to their window start (in hours from now).
     * Windows do not overlap: an appointment within 1h of starting only
     * gets the 1h reminder, so exactly one reminder fires per run.
     *
     * @var array<int, int>
     */
    private const WINDOWS = [24 => 1, 1 => 0];

    public function handle(): int
    {
        foreach (self::WINDOWS as $hours => $startsIn) {
            $this->sendReminders($hours, $startsIn);
        }

        return self::SUCCESS;
    }

    private function sendReminders(int $hours, int $startsIn): void
    {
        $type = "appointment_reminder_{$hours}h";

        $appointments = Appointment::query()
            ->where('status', AppointmentStatus::Confirmed->value)
            ->whereBetween('start_time', [now()->addHours($startsIn), now()->addHours($hours)])
            ->whereDoesntHave('notificationLogs', fn ($query) => $query
                ->where('type', $type)
                ->whereIn('status', ['sent', 'pending']))
            ->with('customer.tenant')
            ->get();

        foreach ($appointments as $appointment) {
            // Mirrors AppointmentObserver::dispatch(): create the pending
            // log before queueing, so the send lifecycle is trackable.
            $notification = new AppointmentReminderNotification($appointment, $hours);
            $notification->createLog($appointment->customer);
            $appointment->customer->notify($notification);
        }

        $this->info("[{$type}] reminders dispatched: {$appointments->count()}.");
        Log::info('Appointment reminders dispatched.', ['type' => $type, 'count' => $appointments->count()]);
    }
}
