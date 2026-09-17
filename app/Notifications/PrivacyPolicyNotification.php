<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Notifications\Channels\TenantMailChannel;
use App\Notifications\Contracts\TenantNotification;
use Filament\Facades\Filament;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers the tenant's GDPR privacy notice to a customer by email. The
 * email carries a link to the notice document, so it always goes out on
 * the mail channel regardless of the customer's preferred channel.
 */
final class PrivacyPolicyNotification extends Notification implements TenantNotification
{
    public function __construct(protected readonly Tenant $tenant) {}

    public function afterSending(object $notifiable, string $channel, mixed $response): void
    {
        // A null response means the channel skipped delivery (it marks the
        // log itself), so only a real send transitions the log to sent.
        if ($response === null) {
            return;
        }

        $this->latestPendingLog($notifiable, 'mail')?->markSent();
    }

    public function createLog(Customer $customer): NotificationLog
    {
        return NotificationLog::query()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'appointment_id' => null,
            'type' => $this->notificationType(),
            'channel' => 'mail',
        ]);
    }

    public function latestPendingLog(Customer $customer, string $channel): ?NotificationLog
    {
        return NotificationLog::latestPendingForCustomer($customer->id, $this->notificationType(), $channel);
    }

    public function notificationType(): string
    {
        return 'privacy_policy';
    }

    public function tenant(): Tenant
    {
        return $this->tenant;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Informativa sulla privacy — {$this->tenant->name}")
            ->markdown('emails.privacy.policy', [
                'cliente_nome' => $notifiable->fullName,
                'salone_nome' => $this->tenant->name,
                'url' => $this->policyUrl(),
            ]);
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [TenantMailChannel::class];
    }

    private function policyUrl(): string
    {
        return route(Filament::getDefaultPanel()->generateRouteName('privacy-policy'), [
            'tenant' => $this->tenant->slug,
        ]);
    }
}
