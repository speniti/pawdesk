<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\Customer;
use App\Models\Tenant;
use App\Notifications\Contracts\TenantNotification;
use Illuminate\Config\Repository as Config;
use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;

class TenantMailChannel extends MailChannel
{
    public function send($notifiable, Notification $notification): ?SentMessage
    {
        /** @var TenantNotification&Notification $notification */
        $tenant = $notification->tenant();

        if (! $tenant->hasMailgunConfigured()) {
            // In locale l'email va comunque al mailer di default (es. Mailpit)
            // per poter provare il flusso end-to-end senza credenziali Mailgun.
            if (! app()->isLocal()) {
                $this->markSkipped($notification, $notifiable, 'Mailgun non configurato per il tenant.');

                return null;
            }

            return $this->sendNotification($notifiable, $notification, $tenant, (string) config('mail.default'));
        }

        $mailerName = $tenant->mailgunMailerName();

        $this->configureTenantMailer($mailerName, $tenant);

        try {
            return $this->sendNotification($notifiable, $notification, $tenant, $mailerName);
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
            'region' => $tenant->mailgunRegion(),
        ]);
    }

    private function markSkipped(TenantNotification $notification, object $notifiable, string $reason): void
    {
        /** @var Customer $notifiable */
        $notification->latestPendingLog($notifiable, 'mail')?->markSkipped($reason);
    }

    private function sendNotification(object $notifiable, TenantNotification&Notification $notification, Tenant $tenant, string $mailerName): ?SentMessage
    {
        $message = $notification->toMail($notifiable);

        if ($tenant->mailFromAddress()) {
            $message->from($tenant->mailFromAddress(), $tenant->mailFromName());
        }

        if (! $notifiable->routeNotificationFor('mail', $notification)) {
            $this->markSkipped($notification, $notifiable, 'Email del cliente mancante.');

            return null;
        }

        return $this->mailer->mailer($mailerName)->send(
            $this->buildView($message),
            array_merge($message->data(), $this->additionalMessageData($notification)),
            $this->messageBuilder($notifiable, $notification, $message)
        );
    }
}
