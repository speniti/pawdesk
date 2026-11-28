<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Notifications\EmailChangeAppliedNotification;
use App\Services\PendingEmailChange;
use App\Services\PendingEmailService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Notification as MailNotification;

/**
 * Public confirmation page for email changes, mirroring the login magic
 * link flow. The 64-character single-use token is equivalent to a login
 * credential (it moves the account identity to the address that received
 * it), which is why possession of the token alone is enough to apply the
 * change, even without an authenticated session.
 */
class EmailChangeConfirmation extends SimplePage
{
    public string $token;

    public function confirm(): void
    {
        $pending = app(PendingEmailService::class)->consume($this->token);

        if ($pending === null || ! $this->applyChange($pending)) {
            Notification::make()
                ->danger()
                ->title("Non è stato possibile applicare il cambio dell'email.")
                ->body('Richiedi un nuovo link dalla pagina del profilo.')
                ->send();

            $this->redirect(Filament::getLoginUrl());

            return;
        }

        Notification::make()
            ->success()
            ->title('Email aggiornata')
            ->body("D'ora in poi usa il nuovo indirizzo per accedere.")
            ->send();

        $this->redirect(Filament::getLoginUrl());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Actions::make([
                    Action::make('confirm')
                        ->label("Conferma il cambio dell'email")
                        ->action(fn () => $this->confirm()),
                ])->fullWidth(),
            ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return "Conferma il cambio dell'email";
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Clicca sul pulsante per applicare il nuovo indirizzo email.';
    }

    public function getTitle(): string|Htmlable
    {
        return "Conferma il cambio dell'email";
    }

    public function mount(string $token): void
    {
        $this->token = $token;

        if (! app(PendingEmailService::class)->isValid($token)) {
            redirect()
                ->to(Filament::getLoginUrl() ?? '/')
                ->with('danger', 'Link non valido o scaduto. Richiedene uno nuovo.');
        }
    }

    /**
     * Apply the confirmed change. Global email uniqueness must still hold
     * at this moment: the address may have been claimed by another user
     * while the link was in flight, in which case the change is rejected.
     */
    private function applyChange(PendingEmailChange $pending): bool
    {
        $user = User::query()->find($pending->userId);

        if (! $user instanceof User) {
            return false;
        }

        if (User::query()
            ->where('email', $pending->email)
            ->whereKeyNot($user->id)
            ->exists()) {
            return false;
        }

        // The address the change moves away from: it must be captured before
        // the save, it stops receiving account email as of now (#95).
        $previousEmail = $user->email;

        try {
            // Possession of the new address was just proven by the confirmed
            // link, so the new email is verified as of now.
            $user->forceFill([
                'email' => $pending->email,
                'email_verified_at' => now(),
            ])->save();
        } catch (UniqueConstraintViolationException) {
            // Fail closed on the uniqueness race.
            return false;
        }

        // Last-chance signal to the old mailbox: the change went through.
        MailNotification::route('mail', $previousEmail)
            ->notify(new EmailChangeAppliedNotification($pending->email));

        return true;
    }
}
