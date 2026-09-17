<?php

declare(strict_types=1);

namespace App\Notifications\Contracts;

use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;

interface TenantNotification
{
    public function createLog(Customer $customer): NotificationLog;

    public function toMail(object $notifiable): MailMessage;

    public function latestPendingLog(Customer $customer, string $channel): ?NotificationLog;

    public function notificationType(): string;

    public function tenant(): Tenant;
}
