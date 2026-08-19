<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\PreferredChannel;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Notifications\Channels\TenantMailChannel;
use App\Notifications\Channels\TenantVonageChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
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

    abstract public function smsContent(): string;

    abstract public function toMail(object $notifiable): MailMessage;

    public function afterSending(object $notifiable, string $channel, mixed $response): void
    {
        // A null response means the channel skipped delivery (it marks the
        // log itself), so only a real send transitions the log to sent.
        if ($response === null) {
            return;
        }

        NotificationLog::latestPending(
            $this->appointment->id,
            $this->notificationType(),
            $this->channelSlug($channel),
        )?->markSent();
    }

    public function createLog(Customer $customer): NotificationLog
    {
        return NotificationLog::query()->create([
            'tenant_id' => $this->appointment->tenant_id,
            'customer_id' => $customer->id,
            'appointment_id' => $this->appointment->id,
            'type' => $this->notificationType(),
            'channel' => $this->resolveChannelSlug($customer),
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
        // The notification is deserialized from the queue without relations loaded.
        $customer = $this->appointment->loadMissing('customer.tenant')->customer;

        NotificationLog::latestPending(
            $this->appointment->id,
            $this->notificationType(),
            $this->resolveChannelSlug($customer),
        )?->markFailed($exception?->getMessage());
    }

    public function getAppointment(): Appointment
    {
        return $this->appointment;
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        return (new VonageMessage)->content($this->smsContent());
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [$notifiable instanceof Customer ? $this->resolveChannelClass($notifiable) : TenantMailChannel::class];
    }

    /**
     * Map the channel identifier received by afterSending() (channel class
     * or slug) to its log slug.
     */
    protected function channelSlug(string $channel): string
    {
        return ($channel === TenantVonageChannel::class || $channel === 'sms') ? 'sms' : 'mail';
    }

    /**
     * Resolve the delivery channel based on the customer's preference, the
     * tenant's channel configuration and the availability of a phone number.
     *
     * @return class-string
     */
    protected function resolveChannelClass(Customer $customer): string
    {
        if ($customer->preferred_channel === PreferredChannel::Sms
            && $customer->tenant?->hasVonageConfigured()
            && filled($customer->phone)) {
            return TenantVonageChannel::class;
        }

        return TenantMailChannel::class;
    }

    /**
     * Resolve the log slug for the channel the customer will be notified on.
     */
    protected function resolveChannelSlug(Customer $customer): string
    {
        return $this->channelSlug($this->resolveChannelClass($customer));
    }
}
