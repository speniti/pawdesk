<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Services\MagicLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $url) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Il tuo link di accesso a PawDesk')
            ->greeting('Ciao!')
            ->line('Abbiamo ricevuto una richiesta di accesso al tuo account.')
            ->action('Accedi', $this->url)
            ->line(sprintf('Il link è valido per %d minuti e può essere usato una sola volta.', MagicLinkService::TTL_MINUTES))
            ->line("Se non hai richiesto tu l'accesso, puoi ignorare questa email.");
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
