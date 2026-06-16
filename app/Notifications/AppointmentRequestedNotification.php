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

    public function smsContent(): string
    {
        $data = $this->emailTemplateData();

        return "PawDesk: richiesta appuntamento per {$data['animale_nome']} il {$data['data']} alle {$data['ora']}. Ti confermeremo presto. {$data['salone_nome']}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuova richiesta di appuntamento')
            ->markdown('emails.appointments.requested', $this->emailTemplateData());
    }
}
