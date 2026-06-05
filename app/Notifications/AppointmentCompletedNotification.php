<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AppointmentCompletedNotification extends BaseAppointmentNotification
{
    public function notificationType(): string
    {
        return 'appointment_completed';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Appuntamento completato')
            ->markdown('emails.appointments.completed', $this->emailTemplateData());
    }
}
