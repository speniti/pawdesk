<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Actions;

use App\Models\Customer;
use App\Notifications\PrivacyPolicyNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class SendPrivacyPolicy extends Action
{
    public static function make(?string $name = 'send-privacy-policy'): static
    {
        return parent::make($name)
            ->label('Informativa Privacy')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedShieldCheck)
            ->modalHeading('Invia Informativa Privacy')
            ->modalDescription("L'informativa sulla privacy verrà nuovamente inviata.")
            ->action(function (Customer $record): void {
                if (blank($record->email)) {
                    Notification::make()
                        ->warning()
                        ->title('Email mancante')
                        ->body("Impossibile inviare l'informativa: il cliente non ha un indirizzo email.")
                        ->send();

                    return;
                }

                $tenant = $record->tenant;

                if (! $tenant->hasMailgunConfigured() && ! app()->isLocal()) {
                    Notification::make()
                        ->warning()
                        ->title('Invio email non configurato')
                        ->body('Configura le credenziali Mailgun nelle impostazioni del salone per inviare l\'informativa.')
                        ->send();

                    return;
                }

                $notification = new PrivacyPolicyNotification($tenant);

                $notification->createLog($record);
                $record->notify($notification);

                $record->update(['gdpr_policy_sent_at' => now()]);

                Notification::make()
                    ->success()
                    ->title('Informativa privacy inviata')
                    ->body("Informativa privacy inviata a $record->email")
                    ->send();
            });
    }
}
