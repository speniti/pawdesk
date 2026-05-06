<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Coat;
use App\Enums\Size;
use App\Enums\Species;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PetSeeder extends Seeder
{
    public function run(Tenant $tenant): void
    {
        if (Customer::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $customers = Customer::factory(8)->for($tenant)->create();

        $profiles = [
            [Species::Dog, Size::Toy, Coat::Short],
            [Species::Dog, Size::Small, Coat::Long],
            [Species::Dog, Size::Medium, Coat::Curly],
            [Species::Dog, Size::Medium, Coat::Spaniel],
            [Species::Dog, Size::Large, Coat::DoubleCoat],
            [Species::Dog, Size::Large, Coat::Flat],
            [Species::Dog, Size::Large, Coat::DoubleCoat],
            [Species::Dog, Size::Giant, Coat::Short],
            [Species::Cat, Size::Small, Coat::ShortHair],
            [Species::Cat, Size::Medium, Coat::Long],
            [Species::Cat, Size::Large, Coat::Long],
            [Species::Other, Size::Small, Coat::Long],
        ];

        collect($profiles)->each(function (array $profile, int $i) use ($tenant, $customers): void {
            [$species, $size, $coat] = $profile;

            Pet::factory()
                ->for($tenant)
                ->for($customers[$i % $customers->count()])
                ->create([
                    'species' => $species->value,
                    'size' => $size->value,
                    'coat' => $coat->value,
                ]);
        });
    }
}
