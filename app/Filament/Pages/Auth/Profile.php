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
use Illuminate\Support\Facades\Notification;

/**
 * In a passwordless app the email IS the account identity, so an email
 * change is never applied directly: it only queues a confirmation magic
 * link to the new address (plus a notice to the current one) and the
 * change is applied by PendingEmailService when the link is confirmed.
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
        if ($record instanceof User
            && array_key_exists('email', $data)
            && $data['email'] !== $record->getAttributeValue('email')) {
            $this->requestEmailChange($record, (string) $data['email']);

            unset($data['email']);
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

        Notification::route('mail', $newEmail)
            ->notify(new EmailChangeConfirmationNotification($url));

        $user->notify(new EmailChangeNoticeNotification($newEmail));

        $this->emailChangeRequested = true;

        // The form keeps showing the current address: nothing changed yet.
        $this->data['email'] = $user->getAttributeValue('email');
    }
}
