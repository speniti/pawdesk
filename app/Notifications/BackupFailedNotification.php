<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Failure alert for the nightly database snapshot (db:snapshot). Deliberately
 * sent synchronously, not queued: if the snapshot failed because the database
 * or the queue is degraded, a queued alert could never be dispatched (#75).
 */
class BackupFailedNotification extends Notification
{
    public function __construct(public readonly string $reason) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('PawDesk: backup del database fallito')
            ->greeting('Il backup notturno del database è fallito.')
            ->line('Motivo: '.$this->reason)
            ->line('Controlla i log del container scheduler e, a sistema ripristinato, verifica il flusso con: php artisan db:snapshot --verify-only.')
            ->line('Il backup del volume su R2 resta attivo, ma senza snapshot verificati la copia potrebbe non essere ripristinabile.');
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
