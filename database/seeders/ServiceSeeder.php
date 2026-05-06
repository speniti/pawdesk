<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Coat;
use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * @return array<string, Service>
     */
    public function run(Tenant $tenant): array
    {
        $services = [];

        $services['bagnetto'] = Service::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Bagnetto Completo'],
            [
                'description' => 'Bagno completo con shampoo e asciugatura',
                'category' => 'bath',
                'duration_minutes' => 60,
                'base_price' => 2500,
                'combinable' => true,
                'status' => ServiceStatus::Active->value,
                'size_prices' => [
                    'toy' => 2000, 'small' => 2500, 'medium' => 3000,
                    'large' => 3500, 'giant' => 4500,
                ],
            ],
        );

        $services['toelettatura'] = Service::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Toelettatura Completa'],
            [
                'description' => 'Toelettatura completa con taglio e styling',
                'category' => 'grooming',
                'duration_minutes' => 90,
                'base_price' => 4000,
                'combinable' => false,
                'status' => ServiceStatus::Active->value,
                'size_prices' => [
                    'toy' => 3500, 'small' => 4000, 'medium' => 5000,
                    'large' => 6000, 'giant' => 7500,
                ],
            ],
        );

        $services['taglio_unghie'] = Service::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Taglio Unghie'],
            [
                'description' => 'Taglio e limatura unghie',
                'category' => 'trimming',
                'duration_minutes' => 15,
                'base_price' => 1000,
                'combinable' => true,
                'status' => ServiceStatus::Active->value,
                'size_prices' => [],
            ],
        );

        $services['pulizia_orecchie'] = Service::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Pulizia Orecchie'],
            [
                'description' => 'Pulizia e controllo orecchie',
                'category' => 'wellness',
                'duration_minutes' => 15,
                'base_price' => 1000,
                'combinable' => true,
                'status' => ServiceStatus::Active->value,
                'size_prices' => [],
            ],
        );

        $services['antiparassitario'] = Service::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Trattamento Antiparassitario'],
            [
                'description' => 'Trattamento antiparassitario completo',
                'category' => 'specialty',
                'duration_minutes' => 30,
                'base_price' => 3000,
                'combinable' => true,
                'status' => ServiceStatus::Active->value,
                'size_prices' => [
                    'toy' => 2500, 'small' => 3000, 'medium' => 3500,
                    'large' => 4000, 'giant' => 5000,
                ],
            ],
        );

        $services['deshedding'] = Service::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'De-shedding'],
            [
                'description' => 'Trattamento per rimozione sottopelo',
                'category' => 'specialty',
                'coat' => Coat::DoubleCoat->value,
                'duration_minutes' => 45,
                'base_price' => 3500,
                'combinable' => true,
                'status' => ServiceStatus::Active->value,
                'size_prices' => [
                    'small' => 3000, 'medium' => 3500,
                    'large' => 4500, 'giant' => 5500,
                ],
            ],
        );

        $services['primo_cucciolo'] = Service::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Primo Cucciolo'],
            [
                'description' => 'Prima esperienza toelettatura per cuccioli',
                'category' => 'grooming',
                'duration_minutes' => 45,
                'base_price' => 2000,
                'combinable' => false,
                'status' => ServiceStatus::Active->value,
                'size_prices' => [
                    'toy' => 1500, 'small' => 2000, 'medium' => 2500,
                ],
            ],
        );

        return $services;
    }
}
