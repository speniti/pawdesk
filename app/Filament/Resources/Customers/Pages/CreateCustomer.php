<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['gdpr_policy_sent_at'] = now();
        $data['marketing_consent_at'] = data_get($data, 'marketing_consent', false) ? now() : null;

        unset($data['marketing_consent']);

        return $data;
    }
}
