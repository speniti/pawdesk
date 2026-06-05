<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AppointmentRequestedNotification extends BaseAppointmentNotification
{
    public function notificationType(): string
    {
        return 'appointment_requested';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuova richiesta di appuntamento')
            ->markdown('emails.appointments.requested', $this->emailTemplateData());
    }
}
