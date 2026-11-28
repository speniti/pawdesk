<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Services\PendingEmailService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Public cancellation page for pending email changes (#95), mirroring the
 * confirmation page. The cancel link is delivered to the CURRENT address, so
 * the legitimate owner can undo a change they did not request without any
 * authenticated session. Like the confirmation, the GET renders a button and
 * only the explicit action mutates state: mail-scanner prefetches must not
 * cancel anything.
 */
class EmailChangeCancellation extends SimplePage
{
    public string $token;

    public function cancel(): void
    {
        $cancelled = app(PendingEmailService::class)->cancelByToken($this->token);

        if (! $cancelled) {
            Notification::make()
                ->danger()
                ->title("Non è stato possibile annullare il cambio dell'email.")
                ->body('Il link non è più valido: la richiesta potrebbe essere già stata annullata, confermata o scaduta.')
                ->send();

            $this->redirect(Filament::getLoginUrl());

            return;
        }

        Notification::make()
            ->success()
            ->title('Cambio email annullato')
            ->body("L'indirizzo attuale resta quello di accesso.")
            ->send();

        $this->redirect(Filament::getLoginUrl());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Actions::make([
                    Action::make('cancel')
                        ->label('Annulla il cambio dell\'email')
                        ->color('danger')
                        ->action(fn () => $this->cancel()),
                ])->fullWidth(),
            ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Annullamento cambio email';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return "Clicca sul pulsante per annullare la richiesta: l'indirizzo attuale resta quello di accesso.";
    }

    public function getTitle(): string|Htmlable
    {
        return 'Annullamento cambio email';
    }

    public function mount(string $token): void
    {
        $this->token = $token;

        if (! app(PendingEmailService::class)->isCancelValid($token)) {
            redirect()
                ->to(Filament::getLoginUrl() ?? '/')
                ->with('danger', 'Link non valido o scaduto. Richiedene uno nuovo.');
        }
    }
}
