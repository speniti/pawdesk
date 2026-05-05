<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'PawDesk Demo',
            'slug' => 'pawdesk-demo',
        ]);

        User::factory()->create([
            'name' => 'Simone Peniti',
            'email' => 'simone@peniti.it',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'tenant_id' => $tenant->id,
        ]);
    }
}
