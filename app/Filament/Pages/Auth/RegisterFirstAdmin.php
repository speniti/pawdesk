<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use App\Services\MagicLinkService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use SensitiveParameter;

class RegisterFirstAdmin extends Register
{
    public static function canView(): bool
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->doesntExist();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
            ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Registrati';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Registrti';
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 404);

        parent::mount();
    }

    public function register(): ?RegistrationResponse
    {
        abort_unless(static::canView(), 404);

        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->mutateFormDataBeforeRegister($this->form->getState());
        $user = User::query()->create($data);

        $url = app(MagicLinkService::class)->issueLink($user);
        $user->notify(new MagicLinkNotification($url));

        Notification::make()
            ->title('Amministratore creato')
            ->body("Controlla la tua email, ti abbiamo inviato un link per effettuare l'accesso.")
            ->success()
            ->send();

        $this->redirect(filament()->getLoginUrl());

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(#[SensitiveParameter] array $data): array
    {
        return [...$data, 'role' => UserRole::Admin];
    }
}
