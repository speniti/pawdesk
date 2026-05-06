<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Password;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetPassword')
                ->label('Reset password')
                ->icon(Heroicon::OutlinedKey)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reset password')
                ->modalDescription("Verrà inviato un link di reset password all'indirizzo email dell'utente.")
                ->action(function (): void {
                    assert($this->record instanceof User);

                    Password::sendResetLink(['email' => $this->record->email]);

                    Notification::make()
                        ->success()
                        ->title('Link inviato')
                        ->body("Link di reset password inviato a {$this->record->email}")
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }
}
