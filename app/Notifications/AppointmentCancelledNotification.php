<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AppointmentCancelledNotification extends BaseAppointmentNotification
{
    public function notificationType(): string
    {
        return 'appointment_cancelled';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Appuntamento cancellato')
            ->markdown('emails.appointments.cancelled', $this->emailTemplateData());
    }
}
