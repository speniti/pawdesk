<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\Tenant;
use App\Notifications\BaseAppointmentNotification;
use Illuminate\Config\Repository as Config;
use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;

class TenantMailChannel extends MailChannel
{
    public function send($notifiable, Notification $notification): ?SentMessage
    {
        /** @var BaseAppointmentNotification $notification */
        $appointment = $notification->getAppointment();

        $tenant = Tenant::findOrFail($appointment->tenant_id);

        if (! $tenant->hasMailgunConfigured()) {
            return null;
        }

        $mailerName = $tenant->mailgunMailerName();

        $this->configureTenantMailer($mailerName, $tenant);

        try {
            $message = $notification->toMail($notifiable);
            $message->mailer($mailerName);

            if ($tenant->mailFromAddress()) {
                $message->from($tenant->mailFromAddress(), $tenant->mailFromName());
            }

            // Replicate parent::send() logic since parent calls toMail() again internally
            if (! $notifiable->routeNotificationFor('mail', $notification)) {
                return null;
            }

            return $this->mailer->mailer($mailerName)->send(
                $this->buildView($message),
                array_merge($message->data(), $this->additionalMessageData($notification)),
                $this->messageBuilder($notifiable, $notification, $message)
            );
        } finally {
            $this->cleanupTenantMailerConfig($mailerName);
        }
    }

    private function cleanupTenantMailerConfig(string $mailerName): void
    {
        /** @var Config $config */
        $config = app(Config::class);
        $config->set("mail.mailers.{$mailerName}", null);
    }

    private function configureTenantMailer(string $mailerName, Tenant $tenant): void
    {
        /** @var \Illuminate\Mail\MailManager $mailManager */
        $mailManager = app('mail.manager');
        $mailManager->purge($mailerName);

        /** @var Config $config */
        $config = app(Config::class);
        $config->set("mail.mailers.{$mailerName}", [
            'transport' => 'tenant-mailgun',
            'secret' => $tenant->mailgunApiKey(),
            'domain' => $tenant->mailgunDomain(),
        ]);
    }
}
