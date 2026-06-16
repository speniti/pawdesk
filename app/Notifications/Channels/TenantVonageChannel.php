<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Notifications\BaseAppointmentNotification;
use App\Services\VonageSmsSender;
use Illuminate\Notifications\Notification;

class TenantVonageChannel
{
    /**
     * E.164 phone number format (e.g. +393331234567).
     */
    private const E164_PATTERN = '/^\+[1-9]\d{6,14}$/';

    public function __construct(private readonly VonageSmsSender $sender) {}

    public function send(object $notifiable, Notification $notification): void
    {
        /** @var BaseAppointmentNotification $notification */
        $appointment = $notification->getAppointment();

        $tenant = Tenant::find($appointment->tenant_id);

        if (! $tenant?->hasVonageConfigured()) {
            return;
        }

        $to = $notifiable->routeNotificationFor('vonage', $notification);

        if (! $this->isValidPhoneNumber($to)) {
            $this->markFailed($notification, 'Numero telefono non valido o mancante (richiesto formato E.164, es. +393331234567).');

            return;
        }

        $this->sender->send(
            $tenant,
            (string) $to,
            $notification->toVonage($notifiable)->content,
            $tenant->vonageSmsSenderId(),
        );
    }

    private function isValidPhoneNumber(mixed $to): bool
    {
        return is_string($to) && preg_match(self::E164_PATTERN, $to) === 1;
    }

    private function markFailed(BaseAppointmentNotification $notification, string $message): void
    {
        NotificationLog::query()
            ->where('appointment_id', $notification->getAppointment()->id)
            ->where('type', $notification->notificationType())
            ->where('channel', 'sms')
            ->where('status', NotificationStatus::Pending->value)
            ->latest()
            ->first()?->update([
                'status' => NotificationStatus::Failed->value,
                'error_message' => $message,
                'failed_at' => now(),
            ]);
    }
}
