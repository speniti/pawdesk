<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The informational notice sent to the CURRENT address when an email change
 * is requested, so the legitimate owner learns that account identity is
 * about to move to another mailbox. No undo action in v1: until the link
 * sent to the new address is confirmed, nothing changes.
 */
class EmailChangeNoticeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $newEmail) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Richiesta di cambio email del tuo account PawDesk')
            ->greeting('Ciao!')
            ->line("È stata richiesta la modifica dell'indirizzo email del tuo account PawDesk.")
            ->line(sprintf('Il nuovo indirizzo indicato è %s.', $this->newEmail))
            ->line("Dopo la conferma, l'accesso avverrà tramite il nuovo indirizzo e questo indirizzo non riceverà più le email dell'account.")
            ->line('Se non hai richiesto tu il cambio, contatta il supporto il prima possibile: nessuna modifica verrà applicata finché il link inviato al nuovo indirizzo non viene confermato.');
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
