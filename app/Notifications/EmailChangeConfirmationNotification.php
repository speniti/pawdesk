<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Services\PendingEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The confirmation magic link sent to the NEW address when an email change
 * is requested. Confirming the link proves possession of the new address
 * and applies the change.
 */
class EmailChangeConfirmationNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $url) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Conferma il cambio email del tuo account PawDesk')
            ->greeting('Ciao!')
            ->line("Abbiamo ricevuto una richiesta di modifica dell'indirizzo email del tuo account PawDesk.")
            ->line('Conferma il nuovo indirizzo cliccando sul pulsante qui sotto.')
            ->action("Conferma il cambio dell'email", $this->url)
            ->line(sprintf('Il link è valido per %d minuti e può essere usato una sola volta.', PendingEmailService::TTL_MINUTES))
            ->line("Se non hai richiesto tu il cambio, puoi ignorare questa email: l'indirizzo attuale resterà invariato.");
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
