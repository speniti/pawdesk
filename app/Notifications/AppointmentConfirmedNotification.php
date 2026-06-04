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
            ->line('Il tuo appuntamento è stato confermato.')
            ->line('Data: '.$this->appointment->start_time->format('d/m/Y H:i'))
            ->line('Ti aspettiamo!');
    }
}
