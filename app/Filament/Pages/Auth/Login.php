<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use App\Services\MagicLinkService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $user = User::query()
            ->where('email', data_get($this->form->getState(), 'email'))
            ->first();

        if ($user !== null) {
            $url = app(MagicLinkService::class)->issueLink($user);
            $user->notify(new MagicLinkNotification($url));
        }

        Notification::make()
            ->success()
            ->title('Link inviato')
            ->body("Se l'email è registrata, riceverai un link per poter effettuare l'accesso.")
            ->send();

        return null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
            ]);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function mount(): void
    {
        if (RegisterFirstAdmin::canView()) {
            redirect(filament()->getRegistrationUrl());

            return;
        }

        parent::mount();
    }
}
