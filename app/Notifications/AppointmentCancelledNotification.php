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
            ->line('Il tuo appuntamento è stato cancellato.')
            ->line('Data: '.$this->appointment->start_time->format('d/m/Y H:i'))
            ->line('Per qualsiasi domanda, non esitare a contattarci.');
    }
}
