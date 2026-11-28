<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the OLD address once an email change has been applied (#95), so
 * the legitimate owner learns the change went through even if they missed
 * the request notice — the last chance to react before the old mailbox
 * stops receiving account email.
 */
class EmailChangeAppliedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $newEmail) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Il cambio email del tuo account PawDesk è stato completato')
            ->greeting('Ciao!')
            ->line("La modifica dell'indirizzo email del tuo account PawDesk è stata confermata.")
            ->line(sprintf("L'account ora usa l'indirizzo %s: da ora le email dell'account arrivano a quel mailbox.", $this->newEmail))
            ->line('Se non sei stato tu a confermare il cambio, contatta il supporto immediatamente.');
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
