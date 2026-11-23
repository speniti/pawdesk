<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use App\Services\MagicLinkService;
use App\Support\MagicLinkRateLimit;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\RateLimiter;

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

        $email = (string) data_get($this->form->getState(), 'email');

        // Independent from the per-IP limit above: caps how many magic links
        // can be sent to the same inbox, whatever the requesting IPs are.
        $limit = MagicLinkRateLimit::fromEmail($email, (string) request()->ip());

        if (RateLimiter::tooManyAttempts((string) $limit->key, $limit->maxAttempts)) {
            $this->getRateLimitedNotification(new TooManyRequestsException(
                static::class,
                'magic-link-email',
                (string) request()->ip(),
                RateLimiter::availableIn((string) $limit->key),
            ))?->send();

            return null;
        }

        RateLimiter::hit((string) $limit->key, $limit->decaySeconds);

        $user = User::query()
            ->where('email', $email)
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
