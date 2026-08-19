<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

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

    /**
     * @return bool|null True when the SMS was sent; null when delivery was
     *                   skipped (the log is marked skipped/failed here).
     */
    public function send(object $notifiable, Notification $notification): ?bool
    {
        /** @var BaseAppointmentNotification $notification */
        $appointment = $notification->getAppointment();

        $tenant = Tenant::findOrFail($appointment->tenant_id);

        if (! $tenant->hasVonageConfigured()) {
            $this->latestPendingLog($notification)?->markSkipped('Vonage non configurato per il tenant.');

            return null;
        }

        $to = $notifiable->routeNotificationFor('vonage', $notification);

        if (! $this->isValidPhoneNumber($to)) {
            $this->latestPendingLog($notification)?->markFailed('Numero telefono non valido o mancante (richiesto formato E.164, es. +393331234567).');

            return null;
        }

        $this->sender->send(
            $tenant,
            (string) $to,
            $notification->toVonage($notifiable)->content,
            $tenant->vonageSmsSenderId(),
        );

        return true;
    }

    private function isValidPhoneNumber(mixed $to): bool
    {
        return is_string($to) && preg_match(self::E164_PATTERN, $to) === 1;
    }

    private function latestPendingLog(BaseAppointmentNotification $notification): ?NotificationLog
    {
        return NotificationLog::latestPending(
            $notification->getAppointment()->id,
            $notification->notificationType(),
            'sms',
        );
    }
}
