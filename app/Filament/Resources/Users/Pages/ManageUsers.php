<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use App\Services\MagicLinkService;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ManageUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false)
                ->modalWidth(Width::Small)
                ->after(function (User $record): void {
                    $record->tenants()->attach(Filament::getTenant());

                    $url = app(MagicLinkService::class)->issueLink($record);

                    $record->notify(new MagicLinkNotification($url));

                    Notification::make()
                        ->success()
                        ->title('Utente creato')
                        ->body("Inviato link di accesso a {$record->email}")
                        ->send();
                }),
        ];
    }
}
