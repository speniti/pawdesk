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
            ->columns(1)
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
                    ->unique(ignoreRecord: true)
                    // Passwordless app: the email IS the account identity, so it is
                    // set once at creation and never edited here (#71). An
                    // admin-side edit would be an immediate, unverified identity
                    // change — a stolen admin session could silently move any
                    // (cross-tenant) user's identity and inherit their
                    // email_verified_at. Users change their own email through the
                    // verified magic-link flow on the profile page; an
                    // admin-initiated verified flow remains possible as a
                    // follow-up if the capability is ever needed. The disabled
                    // field is also skipped on dehydration, so a crafted edit
                    // payload cannot smuggle a new email into the update.
                    ->disabledOn('edit'),

                Select::make('role')
                    ->label('Ruolo')
                    ->options(UserRole::class)
                    ->default(UserRole::Staff)
                    ->required(),
            ]);
    }
}
