<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
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

        $this->call([
            UserSeeder::class,
            ServiceSeeder::class,
            PetSeeder::class,
        ], false, ['tenant' => $tenant]);

        $this->call(AppointmentSeeder::class, false, [
            'tenant' => $tenant,
            'staff' => User::where('email', 'marco@pawdesk-demo.it')->firstOrFail(),
            'customers' => Customer::where('tenant_id', $tenant->id)->get(),
            'pets' => Pet::where('tenant_id', $tenant->id)->get(),
            'services' => Service::where('tenant_id', $tenant->id)->get()->keyBy('name'),
        ]);
    }
}
