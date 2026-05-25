<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use App\Filament\Pages\Tenancy\Schemas\TenantSettingsForm;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditTenantSettings extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Impostazioni';
    }

    public function form(Schema $schema): Schema
    {
        return TenantSettingsForm::configure($schema);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Impostazioni salvate';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['notification_settings'] ??= [];

        return $data;
    }
}
