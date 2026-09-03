<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use App\Models\Tenant;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant as BaseRegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RegisterTenant extends BaseRegisterTenant
{
    public static function canView(): bool
    {
        return Tenant::query()->doesntExist();
    }

    public static function getLabel(): string
    {
        return 'Registra il tuo salone';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome del salone')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    /** @param  array<string, mixed>  $data */
    protected function handleRegistration(array $data): Model
    {
        $tenant = Tenant::create($data);

        $tenant->users()->attach(auth()->user());

        return $tenant;
    }

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        $data['slug'] = Str::slug($data['name']);
        $data['notification_settings'] ??= [];

        return $data;
    }
}
