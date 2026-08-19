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

    public function smsContent(): string
    {
        $data = $this->emailTemplateData();

        return "PawDesk: grazie per la visita di {$data['animale_nome']}! A presto. {$data['salone_nome']}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Appuntamento completato')
            ->markdown('emails.appointments.completed', $this->emailTemplateData());
    }
}
