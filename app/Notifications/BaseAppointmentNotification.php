<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Notifications\Channels\TenantMailChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

abstract class BaseAppointmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $backoff = 60;

    public int $tries = 3;

    public function __construct(protected Appointment $appointment)
    {
        $this->afterCommit();
    }

    abstract public function notificationType(): string;

    abstract public function toMail(object $notifiable): MailMessage;

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

    /**
     * @return array<string, mixed>
     */
    public function emailTemplateData(): array
    {
        $appointment = $this->appointment->load(['customer', 'pet', 'services', 'tenant']);

        return [
            'cliente_nome' => $appointment->customer->fullName,
            'animale_nome' => $appointment->pet->name,
            'data' => $appointment->start_time->format('d/m/Y'),
            'ora' => $appointment->start_time->format('H:i'),
            'servizi' => $appointment->services->pluck('name')->join(', '),
            'salone_nome' => $appointment->tenant->name,
        ];
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
        return [TenantMailChannel::class];
    }
}
