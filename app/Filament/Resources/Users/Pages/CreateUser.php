<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        assert($this->record instanceof User);

        $this->record->tenants()->attach(Filament::getTenant());

        Password::sendResetLink(['email' => $this->record->email]);

        Notification::make()
            ->success()
            ->title('Utente creato')
            ->body("Inviato link di reset password a {$this->record->email}")
            ->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['password'] = Str::password();

        return $data;
    }
}
