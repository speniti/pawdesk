<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->minLength(2)
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    // Global uniqueness is intentional: users authenticate once and may belong to multiple tenants
                    ->unique(ignoreRecord: true),

                Select::make('role')
                    ->label('Ruolo')
                    ->options(UserRole::class)
                    ->default(UserRole::Staff)
                    ->required(),
            ]);
    }
}
