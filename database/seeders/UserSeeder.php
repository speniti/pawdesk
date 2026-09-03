<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /** @return array{admin: User, staff: User} */
    public function run(Tenant $tenant): array
    {
        $admin = User::firstOrCreate(
            ['email' => 'simone@peniti.it'],
            [
                'name' => 'Simone Peniti',
                'role' => UserRole::Admin,
            ],
        );

        $staff = User::firstOrCreate(
            ['email' => 'marco@pawdesk-demo.it'],
            [
                'name' => 'Marco Rossi',
                'role' => UserRole::Staff,
            ],
        );

        $tenant->users()->syncWithoutDetaching([$admin->id, $staff->id]);

        return ['admin' => $admin, 'staff' => $staff];
    }
}
