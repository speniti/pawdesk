<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Services\MagicLinkService;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\RedirectResponse;

class MagicLinkConfirmation extends SimplePage
{
    public string $token;

    public function confirm(): void
    {
        $user = app(MagicLinkService::class)->consume($this->token);

        if ($user === null) {
            Notification::make()
                ->danger()
                ->title('Link non valido o scaduto. Richiedine uno nuovo.')
                ->send();

            $this->redirect(Filament::getLoginUrl());

            return;
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Filament::auth()->login($user);
        session()->regenerate();

        /** @var RedirectResponse $response */
        $response = app(LoginResponse::class)->toResponse(request());

        $this->redirect($response->getTargetUrl());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Actions::make([
                    Action::make('confirm')
                        ->label("Conferma l'accesso")
                        ->action(fn () => $this->confirm()),
                ])->fullWidth(),
            ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return "Conferma l'accesso";
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Clicca sul pulsante per accedere.';
    }

    public function getTitle(): string|Htmlable
    {
        return "Conferma l'accesso";
    }

    public function mount(string $token): void
    {
        $this->token = $token;

        if (! app(MagicLinkService::class)->isValid($token)) {
            redirect()
                ->to(Filament::getLoginUrl() ?? '/')
                ->with('danger', 'Link non valido o scaduto. Richiedine uno nuovo.');
        }
    }
}
