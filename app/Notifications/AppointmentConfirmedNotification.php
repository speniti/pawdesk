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

    public function smsContent(): string
    {
        $data = $this->emailTemplateData();

        return "PawDesk: appuntamento confermato per {$data['animale_nome']} il {$data['data']} alle {$data['ora']}. ({$data['servizi']}) {$data['salone_nome']}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Appuntamento confermato')
            ->markdown('emails.appointments.confirmed', $this->emailTemplateData());
    }
}
