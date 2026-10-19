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
                'variations' => [
                    ['size' => 'small', 'coat' => 'long', 'price' => 3000, 'duration_minutes' => 75],
                    ['size' => 'medium', 'coat' => 'curly', 'price' => 3500, 'duration_minutes' => 75],
                    ['size' => 'medium', 'coat' => 'long', 'price' => 3500, 'duration_minutes' => 75],
                    ['size' => 'large', 'coat' => 'double_coat', 'price' => 4000, 'duration_minutes' => 90],
                    ['size' => 'giant', 'coat' => 'short', 'price' => 4500, 'duration_minutes' => 60],
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
                'variations' => [
                    ['size' => 'small', 'coat' => 'long', 'price' => 4500, 'duration_minutes' => 90],
                    ['size' => 'medium', 'coat' => 'curly', 'price' => 5500, 'duration_minutes' => 120],
                    ['size' => 'medium', 'coat' => 'spaniel', 'price' => 5000, 'duration_minutes' => 105],
                    ['size' => 'large', 'coat' => 'double_coat', 'price' => 6500, 'duration_minutes' => 120],
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
                'variations' => [],
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
                'variations' => [],
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
                'variations' => [
                    ['size' => 'large', 'coat' => 'double_coat', 'price' => 4000, 'duration_minutes' => 45],
                    ['size' => 'giant', 'coat' => 'short', 'price' => 5000, 'duration_minutes' => 45],
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
                'variations' => [
                    ['size' => 'medium', 'coat' => 'double_coat', 'price' => 3500, 'duration_minutes' => 45],
                    ['size' => 'large', 'coat' => 'double_coat', 'price' => 4500, 'duration_minutes' => 60],
                    ['size' => 'giant', 'coat' => 'double_coat', 'price' => 5500, 'duration_minutes' => 75],
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
                'variations' => [
                    ['size' => 'toy', 'coat' => 'short', 'price' => 1500, 'duration_minutes' => 45],
                ],
            ],
        );

        return $services;
    }
}
