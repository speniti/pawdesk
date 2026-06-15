<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use Peniti\FilamentMapbox\Geocoder\AddressInfo;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['address'])) {
            $data['address'] = (string) new AddressInfo(...$data['address']);
        }

        $data['gdpr_policy_sent_at'] = now();
        $data['marketing_consent_at'] = data_get($data, 'marketing_consent', false) ? now() : null;

        unset($data['marketing_consent']);

        return $data;
    }
}
