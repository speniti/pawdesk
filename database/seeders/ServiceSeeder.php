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
    /** @return array<string, Service> */
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
                    ['size' => 'toy', 'price' => 2000],
                    ['size' => 'small', 'price' => 2500],
                    ['size' => 'medium', 'price' => 3000],
                    ['size' => 'large', 'price' => 3500],
                    ['size' => 'giant', 'price' => 4500],
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
                    ['size' => 'toy', 'price' => 3500],
                    ['size' => 'small', 'price' => 4000],
                    ['size' => 'medium', 'price' => 5000],
                    ['size' => 'large', 'price' => 6000],
                    ['size' => 'giant', 'price' => 7500],
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
                    ['size' => 'toy', 'price' => 2500],
                    ['size' => 'small', 'price' => 3000],
                    ['size' => 'medium', 'price' => 3500],
                    ['size' => 'large', 'price' => 4000],
                    ['size' => 'giant', 'price' => 5000],
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
                    ['size' => 'small', 'price' => 3000],
                    ['size' => 'medium', 'price' => 3500],
                    ['size' => 'large', 'price' => 4500],
                    ['size' => 'giant', 'price' => 5500],
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
                    ['size' => 'toy', 'price' => 1500],
                    ['size' => 'small', 'price' => 2000],
                    ['size' => 'medium', 'price' => 2500],
                ],
            ],
        );

        return $services;
    }
}
