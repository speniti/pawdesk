<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Notifications\Messages\MailMessage;

class AppointmentReminderNotification extends BaseAppointmentNotification
{
    public function __construct(Appointment $appointment, public readonly int $hoursBefore)
    {
        parent::__construct($appointment);
    }

    public function notificationType(): string
    {
        return "appointment_reminder_{$this->hoursBefore}h";
    }

    public function smsContent(): string
    {
        $data = $this->emailTemplateData();

        $when = $this->hoursBefore >= 12
            ? "domani ({$data['data']})"
            : "tra poco ({$data['data']} alle {$data['ora']})";

        return "PawDesk: promemoria, appuntamento per {$data['animale_nome']} {$when} alle {$data['ora']}. ({$data['servizi']}) {$data['salone_nome']}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Promemoria appuntamento')
            ->markdown('emails.appointments.reminder', [
                ...$this->emailTemplateData(),
                'ore' => $this->hoursBefore,
            ]);
    }
}
