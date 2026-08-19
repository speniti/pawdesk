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

    public function smsContent(): string
    {
        $data = $this->emailTemplateData();

        return "PawDesk: l'appuntamento del {$data['data']} alle {$data['ora']} per {$data['animale_nome']} e' stato annullato. {$data['salone_nome']}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Appuntamento cancellato')
            ->markdown('emails.appointments.cancelled', $this->emailTemplateData());
    }
}
