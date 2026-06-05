<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AppointmentConfirmedNotification extends BaseAppointmentNotification
{
    public function notificationType(): string
    {
        return 'appointment_confirmed';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Appuntamento confermato')
            ->markdown('emails.appointments.confirmed', $this->emailTemplateData());
    }
}
