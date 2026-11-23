<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Notifications\EmailChangeConfirmationNotification;
use App\Notifications\EmailChangeNoticeNotification;
use App\Services\PendingEmailService;
use Filament\Auth\Pages\EditProfile;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * In a passwordless app the email IS the account identity, so an email
 * change is never applied directly anywhere: the Users resource accepts an
 * email only at creation, and this form just queues a confirmation magic
 * link to the new address (plus a notice to the current one). The change
 * is applied by PendingEmailService only when the link is confirmed.
 */
class Profile extends EditProfile
{
    private bool $emailChangeRequested = false;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...$this->pendingEmailChangeCallout(),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
            ]);
    }

    protected function getSavedNotification(): ?FilamentNotification
    {
        if ($this->emailChangeRequested) {
            return FilamentNotification::make()
                ->success()
                ->title('Link di conferma inviato')
                ->body('Abbiamo inviato un link di conferma al nuovo indirizzo. Il cambio verrà applicato solo dopo la conferma.');
        }

        return parent::getSavedNotification();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record instanceof User && array_key_exists('email', $data)) {
            $requestedEmail = mb_trim((string) $data['email']);

            // A case-variant of the current address is the same mailbox:
            // treat it as a normal save, not a verification cycle.
            if (Str::lower($requestedEmail) !== Str::lower(mb_trim((string) $record->getAttributeValue('email')))) {
                $this->requestEmailChange($record, $requestedEmail);
            }

            // The identity never changes here: only the confirmed magic
            // link applies an email change.
            unset($data['email']);

            // The form keeps showing the current address: nothing changed yet.
            $this->data['email'] = $record->getAttributeValue('email');
        }

        $record->update($data);

        return $record;
    }

    /**
     * @return list<Callout>
     */
    private function pendingEmailChangeCallout(): array
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            return [];
        }

        $pendingEmail = app(PendingEmailService::class)->pendingEmailFor($user);

        if ($pendingEmail === null) {
            return [];
        }

        return [
            Callout::make()
                ->warning()
                ->heading('Cambio email in attesa di conferma')
                ->footer(Text::make(sprintf(
                    'È in attesa di conferma un cambio verso %s. Il cambio verrà applicato solo dopo aver confermato il link inviato al nuovo indirizzo; il link scade dopo %d minuti.',
                    $pendingEmail,
                    PendingEmailService::TTL_MINUTES,
                ))),
        ];
    }

    private function requestEmailChange(User $user, string $newEmail): void
    {
        $url = app(PendingEmailService::class)->issueChange($user, $newEmail);

        // Dispatched after the (optional) save transaction commits, so SMTP
        // latency never holds the transaction open. Without an active
        // transaction (the panel default) the callbacks run immediately.
        DB::afterCommit(function () use ($newEmail, $url, $user): void {
            Notification::route('mail', $newEmail)
                ->notify(new EmailChangeConfirmationNotification($url));

            $user->notify(new EmailChangeNoticeNotification($newEmail));
        });

        $this->emailChangeRequested = true;
    }
}
