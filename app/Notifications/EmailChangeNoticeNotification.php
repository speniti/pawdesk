<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Services\PendingEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The informational notice sent to the CURRENT address when an email change
 * is requested, so the legitimate owner learns that account identity is
 * about to move to another mailbox. Since #95 it carries a signed cancel
 * link (single-use, same TTL as the change): the security review of #71
 * called it "the cheapest control that converts the warning into an
 * effective defence" — until the link sent to the new address is confirmed,
 * nothing changes, and the owner can now undo the request themselves.
 */
class EmailChangeNoticeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $newEmail,
        public readonly string $cancelUrl,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Richiesta di cambio email del tuo account PawDesk')
            ->greeting('Ciao!')
            ->line("È stata richiesta la modifica dell'indirizzo email del tuo account PawDesk.")
            ->line(sprintf('Il nuovo indirizzo indicato è %s.', $this->newEmail))
            ->line("Dopo la conferma, l'accesso avverrà tramite il nuovo indirizzo e questo indirizzo non riceverà più le email dell'account.")
            ->line(sprintf(
                'La richiesta scade da sola dopo %d minuti; se vuoi bloccarla subito, annullala con il pulsante qui sotto.',
                PendingEmailService::TTL_MINUTES,
            ))
            ->action('Annulla la richiesta di cambio', $this->cancelUrl)
            ->line('Se non hai richiesto tu il cambio, annullalo subito con il pulsante qui sopra e contatta il supporto: nessuna modifica verrà applicata finché il link inviato al nuovo indirizzo non viene confermato.');
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
