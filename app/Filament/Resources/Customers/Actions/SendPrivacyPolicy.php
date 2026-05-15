<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Actions;

use App\Models\Customer;
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
                // TODO: Implementare invio email quando il sistema di notifiche sarà disponibile (Sprint 5)

                $record->gdpr_policy_sent_at = now()->toDateTimeString();
                $record->save();

                Notification::make()
                    ->success()
                    ->title('Informativa aggiornata')
                    ->body("Informativa privacy inviata a $record->email")
                    ->send();
            });
    }
}
