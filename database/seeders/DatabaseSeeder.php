<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'pawdesk-demo'],
            [
                'name' => 'PawDesk Demo',
                'primary_color' => '#4F46E5',
                'opening_hours' => [
                    'monday' => [['open' => '09:00', 'close' => '18:00']],
                    'tuesday' => [['open' => '09:00', 'close' => '18:00']],
                    'wednesday' => [['open' => '09:00', 'close' => '18:00']],
                    'thursday' => [['open' => '09:00', 'close' => '18:00']],
                    'friday' => [['open' => '09:00', 'close' => '18:00']],
                    'saturday' => [['open' => '09:00', 'close' => '13:00']],
                    'sunday' => [],
                ],
                'notification_settings' => [],
                'settings' => [],
            ],
        );

        $users = resolve(UserSeeder::class)->run($tenant);
        $services = resolve(ServiceSeeder::class)->run($tenant);
        $pets = resolve(PetSeeder::class)->run($tenant);

        resolve(AppointmentSeeder::class)->run(
            tenant: $tenant,
            staff: $users['staff'],
            customers: $pets['customers'],
            pets: $pets['pets'],
            services: $services,
        );
    }
}
