<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use App\Services\MagicLinkService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendAccessLink')
                ->label('Invia link di accesso')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Invia link di accesso')
                ->modalDescription("Verrà inviato un link di accesso all'indirizzo email dell'utente.")
                ->action(function (): void {
                    /** @var User $record */
                    $record = $this->getRecord();

                    $url = app(MagicLinkService::class)->issueLink($record);

                    $record->notify(new MagicLinkNotification($url));

                    Notification::make()
                        ->success()
                        ->title('Link inviato')
                        ->body("Link di accesso inviato a {$record->email}")
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }
}
