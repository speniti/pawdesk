<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Customer $record */
        $record = $this->getRecord();

        $marketingConsentAt = $record->marketing_consent_at ?? now();

        $data['marketing_consent_at'] = data_get($data, 'marketing_consent', false) ? $marketingConsentAt : null;

        unset($data['marketing_consent']);

        return $data;
    }
}
