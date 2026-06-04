<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Throwable;

abstract class BaseAppointmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Appointment $appointment)
    {
        $this->afterCommit();
    }

    abstract public function notificationType(): string;

    public function afterSending(object $notifiable, string $channel, mixed $response): void
    {
        NotificationLog::query()
            ->where('appointment_id', $this->appointment->id)
            ->where('type', $this->notificationType())
            ->where('status', NotificationStatus::Pending->value)
            ->latest()
            ->first()?->update([
                'status' => NotificationStatus::Sent->value,
                'sent_at' => now(),
            ]);
    }

    public function createLog(Customer $customer): NotificationLog
    {
        return NotificationLog::query()->create([
            'tenant_id' => $this->appointment->tenant_id,
            'customer_id' => $customer->id,
            'appointment_id' => $this->appointment->id,
            'type' => $this->notificationType(),
            'channel' => 'mail',
            'status' => NotificationStatus::Pending->value,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        NotificationLog::query()
            ->where('appointment_id', $this->appointment->id)
            ->where('type', $this->notificationType())
            ->where('status', NotificationStatus::Pending->value)
            ->latest()
            ->first()?->update([
                'status' => NotificationStatus::Failed->value,
                'error_message' => $exception?->getMessage(),
                'failed_at' => now(),
            ]);
    }

    public function getAppointment(): Appointment
    {
        return $this->appointment;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // TODO: resolve from $notifiable->preferred_channel when SMS/WhatsApp are active
        return ['mail'];
    }
}
